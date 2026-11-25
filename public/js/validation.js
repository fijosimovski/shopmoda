/*
 * ShopModa front-end form validation.
 *
 * The rules mirror the Laravel Form Requests in app/Http/Requests, so the user
 * gets the same errors before the form is sent. The server still validates
 * everything; this is only for a better experience.
 *
 * Usage:
 *   <form data-validate novalidate>
 *     <input name="name" data-rules="required|min:3|max:100|name">
 *
 *   - Radio groups: put data-rules on a wrapper element and give it data-field="size".
 *   - Field name in messages: the <label for="..."> text, or data-label="...".
 *   - Custom message for one rule: data-msg-<rule>="..." (e.g. data-msg-required).
 *
 * Rules (separated with |, parameters after ":"):
 *   required, required_with:field, email, numeric, integer, min:n, max:n,
 *   decimal:n, multiple_of:n, same:field, different:field, distinct:group,
 *   password, mimes:jpg,png, max_size:kilobytes, max_files:n,
 *   and the patterns below (name, city, address, phone, label, product_name, slug, coupon).
 *
 * min/max mean characters for text, the value for numeric/integer fields.
 */
(function () {
    'use strict';

    // The shop is Serbian (<html lang="sr-Latn">), the admin panel English (<html lang="en">).
    const EN = (document.documentElement.lang || '').toLowerCase().indexOf('en') === 0;

    // Same regular expressions as in the Form Requests. Messages: [Serbian, English].
    const PATTERNS = {
        name: [/^[\p{L}\s'.-]+$/u, 'sme da sadrži samo slova, razmake, tačke, apostrofe i crtice.', 'may only contain letters, spaces, dots, apostrophes and hyphens.'],
        city: [/^[\p{L}\s'.-]+$/u, 'sme da sadrži samo slova, razmake, tačke, apostrofe i crtice.', 'may only contain letters, spaces, dots, apostrophes and hyphens.'],
        address: [/^[\p{L}\p{N}\s.,'\/#-]+$/u, 'sadrži nedozvoljene znakove.', 'contains characters that are not allowed.'],
        phone: [/^\+?[0-9][0-9\s\/-]{5,19}$/, 'mora biti ispravan broj telefona (cifre, razmaci, / ili -, opciono + na početku).', 'must be a valid phone number (digits, spaces, / or -, optional + at the start).'],
        label: [/^[\p{L}\p{N}\s&'.-]+$/u, "sme da sadrži samo slova, brojeve, razmake i & . ' -", "may only contain letters, numbers, spaces and & . ' -"],
        product_name: [/^[\p{L}\p{N}\s&'".,()+\/-]+$/u, 'sadrži nedozvoljene znakove.', 'contains characters that are not allowed.'],
        slug: [/^[a-z0-9]+(?:-[a-z0-9]+)*$/, 'sme da sadrži samo mala slova, brojeve i pojedinačne crtice (npr. air-max-90).', 'may only contain lowercase letters, numbers and single hyphens (e.g. air-max-90).'],
        coupon: [/^[A-Za-z0-9-]+$/, 'sme da sadrži samo slova, brojeve i crtice.', 'may only contain letters, numbers and hyphens.'],
    };

    // Close to PHP's FILTER_VALIDATE_EMAIL: something@domain.tld
    const EMAIL = /^[^\s@]+@[^\s@.]+(\.[^\s@.]+)+$/;

    // ---------- reading fields ----------

    function isControl(el) {
        return el.matches('input, select, textarea');
    }

    function fieldName(el) {
        return el.dataset.field || el.name;
    }

    function getValue(el) {
        if (!isControl(el)) {
            // wrapper around a radio group
            const checked = el.querySelector('input:checked');
            return checked ? checked.value : '';
        }
        if (el.type === 'file') return el.files;
        if (el.type === 'checkbox' || el.type === 'radio') return el.checked ? el.value : '';
        return el.value.trim();
    }

    function isEmpty(value) {
        return value === '' || value === null || (value instanceof FileList && value.length === 0);
    }

    function labelOf(el) {
        if (el.dataset.label) return el.dataset.label;
        const label = el.id && document.querySelector('label[for="' + el.id + '"]');
        if (label) return label.textContent.replace(/\((optional|opciono)\)/i, '').trim();
        return EN ? 'This field' : 'Ovo polje';
    }

    function findField(form, name) {
        return form.querySelector('[name="' + name + '"], [data-field="' + name + '"]');
    }

    function parseRules(el) {
        return (el.dataset.rules || '').split('|').filter(Boolean).map(function (rule) {
            const i = rule.indexOf(':');
            return i === -1 ? { name: rule, param: null } : { name: rule.slice(0, i), param: rule.slice(i + 1) };
        });
    }

    // ---------- the rules ----------
    // Each check returns an error message, or null when the value is fine.
    // Serbian messages start with "Polje „Label”" (like lang/sr/validation.php), English ones with the label.

    function field(ctx) {
        return EN ? ctx.label : 'Polje „' + ctx.label + '”';
    }

    // t('Serbian text', 'English text')
    function t(sr, en) {
        return EN ? en : sr;
    }

    const CHECKS = {
        required: function (v, p, ctx) {
            if (!isEmpty(v)) return null;
            if (!isControl(ctx.el)) return t('Izaberite: ' + ctx.label.toLowerCase() + '.', 'Please choose a ' + ctx.label.toLowerCase() + '.');
            return field(ctx) + t(' je obavezno.', ' is required.');
        },
        required_with: function (v, p, ctx) {
            const other = findField(ctx.form, p);
            return other && !isEmpty(getValue(other)) && isEmpty(v) ? field(ctx) + t(' je obavezno.', ' is required.') : null;
        },
        email: function (v, p, ctx) {
            return EMAIL.test(v) ? null : field(ctx) + t(' mora biti ispravna email adresa.', ' must be a valid email address.');
        },
        numeric: function (v, p, ctx) {
            return v !== '' && isFinite(v) ? null : field(ctx) + t(' mora biti broj.', ' must be a number.');
        },
        integer: function (v, p, ctx) {
            return /^-?\d+$/.test(v) ? null : field(ctx) + t(' mora biti ceo broj.', ' must be a whole number.');
        },
        min: function (v, p, ctx) {
            if (ctx.isNumber) return Number(v) >= Number(p) ? null : field(ctx) + t(' mora biti najmanje ', ' must be at least ') + p + '.';
            return v.length >= Number(p) ? null : field(ctx) + t(' mora imati najmanje ' + p + ' karaktera.', ' must be at least ' + p + ' characters.');
        },
        max: function (v, p, ctx) {
            if (ctx.isNumber) return Number(v) <= Number(p) ? null : field(ctx) + t(' ne sme biti veće od ', ' may not be greater than ') + p + '.';
            return v.length <= Number(p) ? null : field(ctx) + t(' ne sme imati više od ' + p + ' karaktera.', ' may not be longer than ' + p + ' characters.');
        },
        decimal: function (v, p, ctx) {
            const decimals = (v.split('.')[1] || '').length;
            return decimals <= Number(p) ? null : field(ctx) + t(' sme imati najviše ' + p + ' decimale.', ' may have at most ' + p + ' decimals.');
        },
        multiple_of: function (v, p, ctx) {
            const ratio = Number(v) / Number(p);
            return Math.abs(ratio - Math.round(ratio)) < 1e-9 ? null : field(ctx) + t(' mora ići u koracima od ', ' must go in steps of ') + p + '.';
        },
        same: function (v, p, ctx) {
            const other = findField(ctx.form, p);
            return other && getValue(other) === v ? null : field(ctx) + t(' se ne poklapa.', ' does not match.');
        },
        different: function (v, p, ctx) {
            const other = findField(ctx.form, p);
            if (!other || getValue(other) !== v) return null;
            return field(ctx) + t(' mora se razlikovati od polja „' + labelOf(other) + '”.', ' must be different from the ' + labelOf(other).toLowerCase() + '.');
        },
        distinct: function (v, p, ctx) {
            const duplicates = groupFields(ctx.form, 'distinct:' + p).filter(function (other) {
                return other !== ctx.el && !isEmpty(getValue(other)) && Number(getValue(other)) === Number(v);
            });
            if (!duplicates.length) return null;
            return t('Ista vrednost (' + ctx.label.toLowerCase() + ') je uneta dva puta.', 'The same ' + ctx.label.toLowerCase() + ' is entered twice.');
        },
        password: function (v, p, ctx) {
            if (!/\p{L}/u.test(v)) return field(ctx) + t(' mora sadržati bar jedno slovo.', ' must contain at least one letter.');
            if (!/\p{Lu}/u.test(v) || !/\p{Ll}/u.test(v)) return field(ctx) + t(' mora sadržati bar jedno veliko i jedno malo slovo.', ' must contain at least one uppercase and one lowercase letter.');
            if (!/\d/.test(v)) return field(ctx) + t(' mora sadržati bar jedan broj.', ' must contain at least one number.');
            return null;
        },
        mimes: function (files, p, ctx) {
            const allowed = p.split(',');
            const bad = Array.from(files).find(function (f) {
                return allowed.indexOf(f.name.split('.').pop().toLowerCase()) === -1;
            });
            if (!bad) return null;
            const types = allowed.join(', ').toUpperCase();
            return t('Fajl "' + bad.name + '" nije dozvoljen. Dozvoljeni tipovi: ' + types + '.', '"' + bad.name + '" is not allowed. Allowed types: ' + types + '.');
        },
        max_size: function (files, p, ctx) {
            const tooBig = Array.from(files).find(function (f) { return f.size > Number(p) * 1024; });
            if (!tooBig) return null;
            const mb = Math.round(Number(p) / 1024);
            return t('Fajl "' + tooBig.name + '" je veći od ' + mb + ' MB.', '"' + tooBig.name + '" is larger than ' + mb + ' MB.');
        },
        max_files: function (files, p, ctx) {
            return files.length <= Number(p) ? null : t('Odjednom možete otpremiti najviše ' + p + ' fajlova.', 'You can upload at most ' + p + ' files at once.');
        },
    };

    // ---------- validating ----------

    function groupFields(form, rule) {
        return Array.from(form.querySelectorAll('[data-rules]')).filter(function (el) {
            return el.dataset.rules.split('|').indexOf(rule) !== -1;
        });
    }

    // Returns the first error message for a field, or null.
    function errorFor(el, form) {
        const rules = parseRules(el);
        const value = getValue(el);
        const ctx = {
            el: el,
            form: form,
            label: labelOf(el),
            isNumber: rules.some(function (r) { return r.name === 'numeric' || r.name === 'integer'; }),
        };

        for (const rule of rules) {
            // Like Laravel's "nullable": an empty optional field skips the other rules.
            if (isEmpty(value) && rule.name !== 'required' && rule.name !== 'required_with') continue;

            let message;
            if (CHECKS[rule.name]) {
                message = CHECKS[rule.name](value, rule.param, ctx);
            } else if (PATTERNS[rule.name]) {
                message = PATTERNS[rule.name][0].test(value) ? null : field(ctx) + ' ' + PATTERNS[rule.name][EN ? 2 : 1];
            } else {
                console.warn('validation.js: unknown rule "' + rule.name + '"', el);
                continue;
            }

            if (message) {
                const key = 'msg' + rule.name.replace(/(^|_)(\w)/g, function (m, s, c) { return c.toUpperCase(); });
                return el.dataset[key] || message;
            }
        }
        return null;
    }

    // Shows or hides the Bootstrap error text under the field.
    function showError(el, message) {
        let feedback = el._feedback;
        if (!feedback) {
            feedback = document.createElement('div');
            feedback.className = 'invalid-feedback';
            el.insertAdjacentElement('afterend', feedback);
            el._feedback = feedback;
        }
        feedback.textContent = message || '';
        feedback.classList.toggle('d-block', !!message);
        if (isControl(el)) el.classList.toggle('is-invalid', !!message);
    }

    function validateField(el, form) {
        const message = errorFor(el, form);
        showError(el, message);
        return !message;
    }

    // Fields whose result depends on the changed field (same:, different:, required_with:, distinct:).
    function dependents(form, el) {
        const name = fieldName(el);
        const distinct = parseRules(el).find(function (r) { return r.name === 'distinct'; });
        return Array.from(form.querySelectorAll('[data-rules]')).filter(function (other) {
            if (other === el) return false;
            return parseRules(other).some(function (r) {
                return (['same', 'different', 'required_with'].indexOf(r.name) !== -1 && r.param === name)
                    || (distinct && r.name === 'distinct' && r.param === distinct.param);
            });
        });
    }

    // The field with data-rules that an event came from (a radio inside a wrapper counts too).
    function ruledField(target) {
        return target.closest ? target.closest('[data-rules]') : null;
    }

    function wasShown(el) {
        return el._touched || el.closest('form').dataset.submitted;
    }

    function init(form) {
        form.addEventListener('submit', function (e) {
            form.dataset.submitted = '1';
            const invalid = Array.from(form.querySelectorAll('[data-rules]')).filter(function (el) {
                return !validateField(el, form);
            });

            if (invalid.length) {
                e.preventDefault();
                e.stopImmediatePropagation();
                const first = invalid[0];
                (isControl(first) ? first : first.querySelector('input'))?.focus();
                first.scrollIntoView({ block: 'center', behavior: 'smooth' });
            }
        });

        // Re-check while typing, but only after the field was left once (or the form was submitted).
        function recheck(e) {
            const el = ruledField(e.target);
            if (!el || !form.contains(el)) return;
            if (e.type === 'focusout' && isEmpty(getValue(el)) && !form.dataset.submitted) return;
            if (e.type === 'focusout') el._touched = true;
            if (!wasShown(el)) return;

            validateField(el, form);
            dependents(form, el).forEach(function (other) {
                if (wasShown(other)) validateField(other, form);
            });
        }

        form.addEventListener('input', recheck);
        form.addEventListener('change', recheck);
        form.addEventListener('focusout', recheck);
    }

    document.querySelectorAll('form[data-validate]').forEach(init);
})();
