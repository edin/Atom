(function () {
    if (!window.Atom || typeof window.Atom.setUpdateEngine !== "function" || typeof window.morphdom !== "function") {
        return;
    }

    function isActiveFormElement(element) {
        return element === document.activeElement && element.matches("input, textarea, select");
    }

    window.Atom.setUpdateEngine({
        update: function (current, next) {
            window.morphdom(current, next, {
                childrenOnly: true,
                onBeforeElUpdated: function (fromElement, toElement) {
                    if (isActiveFormElement(fromElement)) {
                        copyFormState(fromElement, toElement);
                    }

                    return true;
                }
            });
        }
    });

    function copyFormState(fromElement, toElement) {
        if (fromElement.type !== "file") {
            toElement.value = fromElement.value;
        }

        if (typeof fromElement.checked === "boolean") {
            toElement.checked = fromElement.checked;
        }
    }
})();
