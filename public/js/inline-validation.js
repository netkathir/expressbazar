(function () {
    const controlsSelector = 'input, select, textarea';
    const validateSelector = `${controlsSelector}:not([type="hidden"]):not([type="submit"]):not([type="button"]):not([type="reset"])`;
    const cssEscape = window.CSS?.escape || ((value) => String(value).replace(/["\\]/g, '\\$&'));

    function labelFor(control) {
        if (control.id) {
            const explicit = document.querySelector(`label[for="${cssEscape(control.id)}"]`);
            if (explicit) {
                return explicit;
            }
        }

        const wrappingLabel = control.closest('label');
        if (wrappingLabel) {
            return wrappingLabel;
        }

        const container = control.closest('.mb-3, .col, [class*="col-"], .form-check, div');
        if (!container) {
            return null;
        }

        return Array.from(container.children).find((child) => child.matches?.('.form-label, label')) || null;
    }

    function addRequiredMarker(control) {
        if (!control.required || control.dataset.requiredMarkerReady === 'true') {
            return;
        }

        const label = labelFor(control);
        if (!label || label.querySelector('.required-symbol')) {
            control.dataset.requiredMarkerReady = 'true';
            return;
        }

        const marker = document.createElement('span');
        marker.className = 'required-symbol';
        marker.setAttribute('aria-hidden', 'true');
        marker.textContent = '*';
        label.append(' ', marker);
        control.dataset.requiredMarkerReady = 'true';
    }

    function messageAnchor(control) {
        if (control.type === 'radio' || control.type === 'checkbox') {
            const group = control.closest('.sf-info-card, fieldset, .mb-3, .col, [class*="col-"]');
            return group || control.closest('label') || control;
        }

        return control.closest('.input-group') || control;
    }

    function messageFor(control, create = true) {
        const anchor = messageAnchor(control);
        let message = anchor.nextElementSibling;

        if (!message?.classList.contains('field-validation-message') && create) {
            message = document.createElement('div');
            message.className = 'field-validation-message invalid-feedback';
            anchor.after(message);
        }

        return message?.classList.contains('field-validation-message') ? message : null;
    }

    function friendlyName(control) {
        const label = labelFor(control);
        const text = label?.textContent?.replace('*', '').trim();

        if (text) {
            return text;
        }

        return (control.name || 'This field')
            .replace(/\[\]$/, '')
            .replace(/[_-]+/g, ' ')
            .replace(/\b\w/g, (letter) => letter.toUpperCase());
    }

    function validationText(control) {
        const name = friendlyName(control);
        const validity = control.validity;

        if (validity.valueMissing) {
            if (control.tagName === 'SELECT') {
                return `Please select ${name.toLowerCase()}.`;
            }

            if (control.type === 'radio' || control.type === 'checkbox') {
                return `Please choose ${name.toLowerCase()}.`;
            }

            return `${name} is required.`;
        }

        if (validity.typeMismatch) {
            return `Please enter a valid ${control.type === 'email' ? 'email address' : control.type}.`;
        }

        if (validity.rangeUnderflow) {
            return `${name} must be at least ${control.min}.`;
        }

        if (validity.rangeOverflow) {
            return `${name} must be no more than ${control.max}.`;
        }

        if (validity.stepMismatch) {
            return `Please enter a valid value for ${name.toLowerCase()}.`;
        }

        if (validity.tooShort) {
            return `${name} must be at least ${control.minLength} characters.`;
        }

        if (validity.tooLong) {
            return `${name} must be ${control.maxLength} characters or fewer.`;
        }

        if (validity.patternMismatch) {
            return `Please match the requested format for ${name.toLowerCase()}.`;
        }

        return control.validationMessage || `Please check ${name.toLowerCase()}.`;
    }

    function clearControl(control) {
        control.classList.remove('is-invalid');
        control.removeAttribute('aria-invalid');

        const message = messageFor(control, false);
        if (message) {
            message.textContent = '';
        }
    }

    function setInvalid(control) {
        control.classList.add('is-invalid');
        control.setAttribute('aria-invalid', 'true');

        const message = messageFor(control);
        if (message) {
            message.textContent = validationText(control);
        }
    }

    function validateControl(control) {
        if (control.disabled || control.readOnly || !control.willValidate) {
            clearControl(control);
            return true;
        }

        const valid = control.checkValidity();
        if (valid) {
            clearControl(control);
            return true;
        }

        setInvalid(control);
        return false;
    }

    function controlsFor(form) {
        const controls = Array.from(form.querySelectorAll(validateSelector));
        const seenRadioGroups = new Set();

        return controls.filter((control) => {
            if (control.type !== 'radio') {
                return true;
            }

            const key = control.name || control.id || Math.random().toString(36);
            if (seenRadioGroups.has(key)) {
                return false;
            }

            seenRadioGroups.add(key);
            return true;
        });
    }

    function validateForm(form) {
        let firstInvalid = null;

        controlsFor(form).forEach((control) => {
            const valid = validateControl(control);
            if (!valid && !firstInvalid) {
                firstInvalid = control;
            }
        });

        if (firstInvalid) {
            firstInvalid.focus({ preventScroll: true });
            firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return false;
        }

        return true;
    }

    function prepareForm(form) {
        if (form.dataset.inlineValidationReady === 'true') {
            return;
        }

        const controls = Array.from(form.querySelectorAll(validateSelector));
        if (!controls.some((control) => control.required || control.willValidate)) {
            return;
        }

        form.noValidate = true;
        form.dataset.inlineValidationReady = 'true';

        controls.forEach((control) => {
            addRequiredMarker(control);
            control.addEventListener('input', () => validateControl(control));
            control.addEventListener('change', () => validateControl(control));
        });
    }

    function bootInlineValidation() {
        document.querySelectorAll('form').forEach(prepareForm);
    }

    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || form.dataset.inlineValidationReady !== 'true') {
            return;
        }

        if (!validateForm(form)) {
            event.preventDefault();
            event.stopImmediatePropagation();
        }
    }, true);

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bootInlineValidation);
    } else {
        bootInlineValidation();
    }
})();
