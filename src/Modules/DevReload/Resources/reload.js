(function () {
    var script = document.currentScript;
    var interval = Number(script && script.getAttribute("data-interval") || 1000);
    var endpoint = script && script.src
        ? script.src.replace(/\/resources\/reload\.js(?:\?.*)?$/, "/reload-version")
        : "/atom/dev/reload-version";
    var current = null;
    var pending = false;

    function check() {
        if (pending) {
            return;
        }

        pending = true;

        fetch(endpoint, {
            cache: "no-store",
            headers: {
                "X-Requested-With": "Atom"
            }
        })
            .then(function (response) {
                return response.ok ? response.json() : null;
            })
            .then(function (data) {
                if (!data || !data.version) {
                    return;
                }

                if (current === null) {
                    current = data.version;
                    return;
                }

                if (current !== data.version) {
                    window.location.reload();
                }
            })
            .catch(function () {
            })
            .finally(function () {
                pending = false;
            });
    }

    window.setInterval(check, interval);
    check();
})();
