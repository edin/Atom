(function () {
    var actionAttribute = "atom:action";
    var changeAttribute = "atom:change";
    var inputAttribute = "atom:input";
    var submitAttribute = "atom:submit";
    var navigateAttribute = "atom:navigate";
    var preserveStateAttribute = "atom:preserve-state";
    var updateRootAttribute = "atom:update-root";
    var updateEngine = {
        update: function (current, next) {
            var state = captureUpdateState(current);

            current.innerHTML = next.innerHTML;
            restoreUpdateState(current, state);
        }
    };
    var inputDebounceMs = 300;

    function captureUpdateState(root) {
        return {
            focus: captureFocus(root),
            scroll: {
                x: window.scrollX || window.pageXOffset || 0,
                y: window.scrollY || window.pageYOffset || 0
            },
            elementScroll: captureElementScroll(root)
        };
    }

    function captureFocus(root) {
        var active = document.activeElement;

        if (!active || !root.contains(active) || !isFocusableStateElement(active)) {
            return null;
        }

        return {
            selector: stableSelector(active),
            value: active.value,
            checked: active.checked,
            selectionStart: active.selectionStart,
            selectionEnd: active.selectionEnd,
            selectionDirection: active.selectionDirection
        };
    }

    function captureElementScroll(root) {
        var state = [];
        var elements = root.querySelectorAll("[id]");

        for (var index = 0; index < elements.length; index++) {
            if (elements[index].scrollTop !== 0 || elements[index].scrollLeft !== 0) {
                state.push({
                    selector: "#" + cssEscape(elements[index].id),
                    top: elements[index].scrollTop,
                    left: elements[index].scrollLeft
                });
            }
        }

        return state;
    }

    function restoreUpdateState(root, state) {
        restoreFocus(root, state.focus);
        restoreElementScroll(root, state.elementScroll);
        window.scrollTo(state.scroll.x, state.scroll.y);
    }

    function restoreFocus(root, state) {
        if (state === null || state.selector === null) {
            return;
        }

        var element = root.querySelector(state.selector);
        if (!element || !isFocusableStateElement(element)) {
            return;
        }

        if (element.type !== "file") {
            element.value = state.value;
        }

        if (typeof state.checked === "boolean") {
            element.checked = state.checked;
        }

        element.focus({ preventScroll: true });

        if (canSelect(element) && state.selectionStart !== null && state.selectionEnd !== null) {
            element.setSelectionRange(state.selectionStart, state.selectionEnd, state.selectionDirection || "none");
        }
    }

    function restoreElementScroll(root, state) {
        for (var index = 0; index < state.length; index++) {
            var element = root.querySelector(state[index].selector);
            if (element) {
                element.scrollTop = state[index].top;
                element.scrollLeft = state[index].left;
            }
        }
    }

    function isFocusableStateElement(element) {
        return element.matches("input, textarea, select");
    }

    function canSelect(element) {
        return element.matches("textarea, input[type=\"text\"], input[type=\"search\"], input[type=\"url\"], " +
            "input[type=\"tel\"], input[type=\"password\"], input[type=\"email\"], input[type=\"number\"]");
    }

    function stableSelector(element) {
        if (element.id) {
            return "#" + cssEscape(element.id);
        }

        if (element.name) {
            return element.tagName.toLowerCase() + "[name=\"" + attributeEscape(element.name) + "\"]";
        }

        return null;
    }

    function cssEscape(value) {
        if (window.CSS && typeof window.CSS.escape === "function") {
            return window.CSS.escape(value);
        }

        return value.replace(/[^a-zA-Z0-9_-]/g, "\\$&");
    }

    function attributeEscape(value) {
        return String(value).replace(/\\/g, "\\\\").replace(/"/g, "\\\"");
    }

    function csrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        if (meta !== null) {
            return meta.getAttribute("content") || "";
        }

        var input = document.querySelector('input[name="_token"]');
        return input === null ? "" : input.value || "";
    }

    function request(method, url, body, done, fail, headers) {
        var xhr = new XMLHttpRequest();

        xhr.open(method, url, true);
        xhr.setRequestHeader("X-Requested-With", "Atom");
        headers = headers || {};
        var token = csrfToken();
        if (!/^(GET|HEAD|OPTIONS)$/i.test(method) && token !== "" && !headers["X-CSRF-Token"]) {
            headers["X-CSRF-Token"] = token;
        }
        Object.keys(headers).forEach(function (name) {
            xhr.setRequestHeader(name, headers[name]);
        });
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

    function updatePage(html, url) {
        var next = parsePage(html);

        document.title = next.title;
        syncState(next);
        updateDocument(document.body, next.body);

        if (url && window.location.href !== url) {
            window.history.pushState({}, "", url);
        }
    }

    function updateDocument(current, next) {
        var currentRoot = updateRoot(current);
        var nextRoot = updateRoot(next);

        if (currentRoot !== null && nextRoot !== null) {
            window.Atom.update(currentRoot, nextRoot);
            return;
        }

        window.Atom.update(current, next);
    }

    function updateRoot(root) {
        return root.querySelector("[" + updateRootAttribute.replace(":", "\\:") + "]");
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

        if (form.hasAttribute(submitAttribute)) {
            return form.getAttribute(submitAttribute) || "";
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
                updatePage(html, responseUrl);
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
                updatePage(html, responseUrl);
                setBusy(false);
            },
            function () {
                setBusy(false);
                target && (target.disabled = false);
                showError("Action failed before a response was received.");
            }
        );
    }

    function fieldValue(field) {
        if (field.type === "checkbox") {
            return field.checked ? (field.value || "1") : "";
        }

        if (field.type === "radio") {
            return field.checked ? field.value : "";
        }

        if (field.multiple && field.options) {
            var values = [];

            for (var index = 0; index < field.options.length; index++) {
                if (field.options[index].selected) {
                    values.push(field.options[index].value);
                }
            }

            return values.join(",");
        }

        return field.value === undefined ? "" : field.value;
    }

    function eventFormData(field) {
        var form = field.form || (field.closest ? field.closest("form") : null);
        var body = form === null ? new FormData() : new FormData(form);
        var name = field.getAttribute("name") || "";

        if (form === null && name !== "") {
            body.append(name, fieldValue(field));
        }

        return body;
    }

    function invokeEventAction(action, field, eventName) {
        var body = eventFormData(field);
        var name = field.getAttribute("name") || "";
        var value = fieldValue(field);

        if (!body.has("_action")) {
            body.append("_action", action);
        }

        if (!body.has("_state")) {
            body.append("_state", currentState());
        }

        setBusy(true);

        request(
            "POST",
            window.location.href,
            body,
            function (html, responseUrl) {
                updatePage(html, responseUrl);
                setBusy(false);
            },
            function () {
                setBusy(false);
                showError("Action failed before a response was received.");
            },
            {
                "X-Atom-Intent": "action",
                "X-Atom-Event": eventName,
                "X-Atom-Field": name,
                "X-Atom-Value": value
            }
        );
    }

    function debounceInputAction(action, field) {
        if (field.__atomInputTimer) {
            window.clearTimeout(field.__atomInputTimer);
        }

        field.__atomInputTimer = window.setTimeout(function () {
            field.__atomInputTimer = null;
            invokeEventAction(action, field, "input");
        }, inputDebounceMs);
    }

    function followLink(anchor) {
        var body = null;
        var method = "GET";
        var headers = null;

        if (anchor.hasAttribute(preserveStateAttribute)) {
            method = "POST";
            body = new FormData();
            body.append("_state", currentState());
            headers = {
                "X-Atom-Intent": "navigate",
                "X-Atom-Method": "GET"
            };
        }

        setBusy(true);
        request(
            method,
            anchor.href,
            body,
            function (html, responseUrl) {
                updatePage(html, responseUrl);
                setBusy(false);
            },
            function () {
                setBusy(false);
                window.location.href = anchor.href;
            },
            headers
        );
    }

    document.addEventListener("submit", function (event) {
        var form = event.target;

        if (!form || form.tagName !== "FORM" || !isEnhancedForm(form)) {
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

        if (button === null || button.form === null || !isEnhancedForm(button.form)) {
            return;
        }

        if (typeof window.XMLHttpRequest !== "function" || typeof window.FormData !== "function") {
            return;
        }

        event.preventDefault();
        event.__atomHandled = true;
        submitForm(button.form, button);
    }, true);

    function isEnhancedForm(form) {
        return form.hasAttribute(actionAttribute) || form.hasAttribute(submitAttribute);
    }

    document.addEventListener("click", function (event) {
        if (event.__atomHandled) {
            return;
        }

        var element = event.target.closest ? event.target.closest("[" + actionAttribute.replace(":", "\\:") + "]") : null;

        if (element === null || element.tagName === "FORM") {
            return;
        }

        if (
            element.form !== undefined &&
            element.form !== null &&
            element.matches("button[type=\"submit\"], input[type=\"submit\"]")
        ) {
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

    document.addEventListener("change", function (event) {
        var element = event.target.closest ? event.target.closest("[" + changeAttribute.replace(":", "\\:") + "]") : null;

        if (element === null) {
            return;
        }

        if (typeof window.XMLHttpRequest !== "function" || typeof window.FormData !== "function") {
            return;
        }

        var action = element.getAttribute(changeAttribute) || "";
        if (action === "") {
            return;
        }

        invokeEventAction(action, element, "change");
    }, true);

    document.addEventListener("input", function (event) {
        var element = event.target.closest ? event.target.closest("[" + inputAttribute.replace(":", "\\:") + "]") : null;

        if (element === null) {
            return;
        }

        if (typeof window.XMLHttpRequest !== "function" || typeof window.FormData !== "function") {
            return;
        }

        var action = element.getAttribute(inputAttribute) || "";
        if (action === "") {
            return;
        }

        debounceInputAction(action, element);
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
                updatePage(html);
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
        setUpdateEngine: function (engine) {
            if (engine && typeof engine.update === "function") {
                updateEngine = engine;
            }
        },
        update: function (current, next) {
            updateEngine.update(current, next);
        },
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
                    updatePage(html, responseUrl);
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
