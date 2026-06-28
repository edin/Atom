(function () {
    var actionAttribute = "atom:action";
    var navigateAttribute = "atom:navigate";

    function request(method, url, body, done, fail) {
        var xhr = new XMLHttpRequest();

        xhr.open(method, url, true);
        xhr.setRequestHeader("X-Requested-With", "Atom");
        xhr.onreadystatechange = function () {
            if (xhr.readyState !== 4) {
                return;
            }

            if (xhr.status >= 200 && xhr.status < 500) {
                done(xhr.responseText, xhr.responseURL || url);
                return;
            }

            fail();
        };
        xhr.onerror = fail;
        xhr.send(body);
    }

    function parsePage(html) {
        var page = document.implementation.createHTMLDocument("");
        page.documentElement.innerHTML = html;

        return page;
    }

    function morphPage(html, url) {
        var next = parsePage(html);

        document.title = next.title;
        syncState(next);
        document.body.innerHTML = next.body.innerHTML;

        if (url && window.location.href !== url) {
            window.history.pushState({}, "", url);
        }
    }

    function syncState(next) {
        var current = document.querySelector('meta[name="atom-state"]');
        var incoming = next.querySelector('meta[name="atom-state"]');

        if (incoming === null) {
            current && current.remove();
            return;
        }

        if (current === null) {
            document.head.appendChild(incoming.cloneNode(true));
            return;
        }

        current.setAttribute("content", incoming.getAttribute("content") || "");
    }

    function setBusy(busy) {
        document.documentElement.classList.toggle("is-atom-loading", busy);
    }

    function showError(message) {
        var response = document.querySelector(".response-preview pre");

        if (response !== null) {
            response.textContent = message;
        }
    }

    function sameOrigin(url) {
        return new URL(url, window.location.href).origin === window.location.origin;
    }

    function enhancedLink(anchor) {
        if (anchor.target !== "" || anchor.hasAttribute("download")) {
            return false;
        }

        var url = new URL(anchor.href, window.location.href);

        return anchor.hasAttribute(navigateAttribute) && sameOrigin(url.href);
    }

    function formAction(form, submitter) {
        if (submitter !== null && submitter.hasAttribute(actionAttribute)) {
            return submitter.getAttribute(actionAttribute) || "";
        }

        return form.getAttribute(actionAttribute) || "";
    }

    function currentState() {
        var meta = document.querySelector('meta[name="atom-state"]');

        return meta === null ? "" : meta.getAttribute("content") || "";
    }

    function submitForm(form, submitter) {
        var button = submitter || form.querySelector("button[type=\"submit\"]");
        var url = form.action || window.location.href;
        var method = (form.getAttribute("method") || "GET").toUpperCase();
        var body = method === "GET" ? null : new FormData(form);
        var action = formAction(form, submitter);

        if (action !== "" && body !== null && !body.has("_action")) {
            body.append("_action", action);
        }

        if (body !== null && !body.has("_state")) {
            body.append("_state", currentState());
        }

        setBusy(true);
        button && (button.disabled = true);

        request(
            method,
            url,
            body,
            function (html, responseUrl) {
                morphPage(html, responseUrl);
                setBusy(false);
            },
            function () {
                setBusy(false);
                button && (button.disabled = false);
                showError("Request failed before a response was received.");
            }
        );
    }

    function invokeAction(action, target) {
        var body = new FormData();

        body.append("_action", action);
        body.append("_state", currentState());

        setBusy(true);
        target && (target.disabled = true);

        request(
            "POST",
            window.location.href,
            body,
            function (html, responseUrl) {
                morphPage(html, responseUrl);
                setBusy(false);
            },
            function () {
                setBusy(false);
                target && (target.disabled = false);
                showError("Action failed before a response was received.");
            }
        );
    }

    function followLink(anchor) {
        setBusy(true);
        request(
            "GET",
            anchor.href,
            null,
            function (html, responseUrl) {
                morphPage(html, responseUrl);
                setBusy(false);
            },
            function () {
                setBusy(false);
                window.location.href = anchor.href;
            }
        );
    }

    document.addEventListener("submit", function (event) {
        var form = event.target;

        if (!form || form.tagName !== "FORM" || !form.hasAttribute(actionAttribute)) {
            return;
        }

        if (typeof window.XMLHttpRequest !== "function" || typeof window.FormData !== "function") {
            return;
        }

        event.preventDefault();
        event.__atomHandled = true;
        submitForm(form, event.submitter || null);
    }, true);

    document.addEventListener("click", function (event) {
        var button = event.target.closest ? event.target.closest("button[type=\"submit\"]") : null;

        if (button === null || button.form === null || !button.form.hasAttribute(actionAttribute)) {
            return;
        }

        if (typeof window.XMLHttpRequest !== "function" || typeof window.FormData !== "function") {
            return;
        }

        event.preventDefault();
        event.__atomHandled = true;
        submitForm(button.form, button);
    }, true);

    document.addEventListener("click", function (event) {
        if (event.__atomHandled) {
            return;
        }

        var element = event.target.closest ? event.target.closest("[" + actionAttribute.replace(":", "\\:") + "]") : null;

        if (element === null || element.tagName === "FORM") {
            return;
        }

        if (element.form !== undefined && element.form !== null) {
            return;
        }

        if (typeof window.XMLHttpRequest !== "function" || typeof window.FormData !== "function") {
            return;
        }

        var action = element.getAttribute(actionAttribute) || "";
        if (action === "") {
            return;
        }

        event.preventDefault();
        invokeAction(action, element);
    }, true);

    document.addEventListener("click", function (event) {
        var anchor = event.target.closest ? event.target.closest("a") : null;

        if (anchor === null || !enhancedLink(anchor)) {
            return;
        }

        event.preventDefault();
        followLink(anchor);
    }, true);

    window.addEventListener("popstate", function () {
        setBusy(true);
        request(
            "GET",
            window.location.href,
            null,
            function (html) {
                morphPage(html);
                setBusy(false);
            },
            function () {
                setBusy(false);
                window.location.reload();
            }
        );
    });

    window.Atom = {
        enhanced: true,
        action: function (name) {
            invokeAction(name, null);
        },
        navigate: function (url) {
            setBusy(true);
            request(
                "GET",
                url,
                null,
                function (html, responseUrl) {
                    morphPage(html, responseUrl);
                    setBusy(false);
                },
                function () {
                    setBusy(false);
                    window.location.href = url;
                }
            );
        }
    };

})();
