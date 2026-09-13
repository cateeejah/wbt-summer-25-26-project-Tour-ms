/* ================================================================
   SHARED CLIENT-SIDE VALIDATION
   validateForm(form) checks every input against its HTML attributes
   before the form is submitted. This is a fast first check only —
   the PHP controllers validate everything again on the server,
   which is the real gate. JS validation exists purely so the user
   gets immediate feedback without a round trip.

   Recognised rules (set these as plain HTML attributes on inputs):
     required              -> must not be left empty
     type="email"          -> must look like an email address
     type="number" min/max -> must be a number inside that range
     type="date" min       -> must not be before that date
     data-min="8"          -> minimum number of characters
     data-match="password" -> must equal the field with that name
     data-phone="1"        -> must look like a phone number
     data-label="Name"     -> friendly name used in the error message
   ================================================================ */

function showFieldError(input, message) {
    input.classList.add('is-invalid');
    var note = document.createElement('span');
    note.className = 'field-error';
    note.textContent = message;
    input.parentNode.appendChild(note);
}

function clearFieldErrors(form) {
    form.querySelectorAll('.field-error').forEach(function (el) { el.remove(); });
    form.querySelectorAll('.is-invalid').forEach(function (el) { el.classList.remove('is-invalid'); });
}

function validateForm(form) {
    clearFieldErrors(form);
    var valid = true;
    var firstBad = null;

    form.querySelectorAll('input, select, textarea').forEach(function (input) {
        if (input.type === 'hidden' || input.type === 'checkbox' || input.disabled) { return; }

        var value = (input.value || '').trim();
        var label = input.getAttribute('data-label') || input.name || 'This field';
        var message = '';

        if (input.hasAttribute('required') && value === '') {
            message = label + ' is required.';

        } else if (value !== '' && input.dataset.min && value.length < parseInt(input.dataset.min, 10)) {
            message = label + ' needs at least ' + input.dataset.min + ' characters.';

        } else if (value !== '' && input.type === 'email' &&
                   !/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(value)) {
            message = 'Enter a valid email address.';

        } else if (value !== '' && input.dataset.phone &&
                   !/^[0-9+\-\s()]{6,20}$/.test(value)) {
            message = 'Enter a valid contact number.';

        } else if (value !== '' && input.dataset.match) {
            var other = form.querySelector('[name="' + input.dataset.match + '"]');
            if (other && value !== other.value.trim()) {
                message = 'The two passwords do not match.';
            }

        } else if (value !== '' && input.type === 'number') {
            var num = parseFloat(value);
            if (isNaN(num)) {
                message = label + ' must be a number.';
            } else if (input.min !== '' && num < parseFloat(input.min)) {
                message = label + ' cannot be less than ' + input.min + '.';
            } else if (input.max !== '' && num > parseFloat(input.max)) {
                message = label + ' cannot be more than ' + input.max + '.';
            }

        } else if (value !== '' && input.type === 'date' && input.min && value < input.min) {
            message = label + ' cannot be in the past.';
        }

        if (message) {
            showFieldError(input, message);
            valid = false;
            if (!firstBad) { firstBad = input; }
        }
    });

    if (firstBad) { firstBad.focus(); }
    return valid;
}

/* Extra cross-field check used on the tour/offering forms: end date
   must not be before start date. Called in addition to validateForm(). */
function validateDateRange(form, startName, endName) {
    var start = form.querySelector('[name="' + startName + '"]');
    var end = form.querySelector('[name="' + endName + '"]');
    if (start && end && start.value && end.value && end.value < start.value) {
        showFieldError(end, 'End date must be on or after the start date.');
        end.focus();
        return false;
    }
    return true;
}
