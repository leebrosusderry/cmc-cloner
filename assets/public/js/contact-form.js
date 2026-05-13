/* CMC Cloner — Contact form hydration + mailto handler.
 *
 * Cloned Contact Us pages carry an opaque mount:
 *     <div class="cmc-contact-form-mount" data-mailto="..."></div>
 *
 * We keep the mount minimal so the AI (which rewrites the skeleton) and
 * wp_kses_post (which strips <form>/<input> for users without
 * unfiltered_html) both leave it alone. This script hydrates each mount
 * into the real <form> at page load, then intercepts submits to open
 * the user's native mail client via mailto: — no server round-trip.
 */
(function () {
    if (typeof document === 'undefined') return;

    var FORM_HTML =
        '<form class="cmc-contact-form">' +
          '<div class="cmc-contact-form__row">' +
            '<label class="cmc-contact-form__field">' +
              '<span class="cmc-contact-form__label">Your name</span>' +
              '<input type="text" name="name" required autocomplete="name" class="cmc-contact-form__input" placeholder="Jane Doe">' +
            '</label>' +
            '<label class="cmc-contact-form__field">' +
              '<span class="cmc-contact-form__label">Email</span>' +
              '<input type="email" name="email" required autocomplete="email" class="cmc-contact-form__input" placeholder="jane@example.com">' +
            '</label>' +
          '</div>' +
          '<label class="cmc-contact-form__field">' +
            '<span class="cmc-contact-form__label">Subject</span>' +
            '<input type="text" name="subject" class="cmc-contact-form__input" placeholder="How can we help?">' +
          '</label>' +
          '<label class="cmc-contact-form__field">' +
            '<span class="cmc-contact-form__label">Message</span>' +
            '<textarea name="message" required rows="5" class="cmc-contact-form__textarea" placeholder="Tell us more..."></textarea>' +
          '</label>' +
          '<input type="text" name="website" tabindex="-1" autocomplete="off" class="cmc-contact-form__hp" aria-hidden="true">' +
          '<button type="submit" class="button primary cmc-contact-cta cmc-contact-form__submit">Send via Email</button>' +
        '</form>';

    function hydrate() {
        var mounts = document.querySelectorAll('.cmc-contact-form-mount:not([data-cmc-hydrated])');
        for (var i = 0; i < mounts.length; i++) {
            var mount = mounts[i];
            var mailto = (mount.getAttribute('data-mailto') || '').trim();
            mount.setAttribute('data-cmc-hydrated', '1');
            mount.innerHTML = FORM_HTML;
            var form = mount.querySelector('form.cmc-contact-form');
            if (form && mailto) {
                form.setAttribute('data-mailto', mailto);
            }
        }
    }

    function enc(s) {
        // encodeURIComponent converts \n to %0A. Outlook / Apple Mail
        // prefer CRLF in the body so we promote %0A to %0D%0A.
        return encodeURIComponent(String(s)).replace(/%0A/g, '%0D%0A');
    }

    function handleSubmit(e) {
        var form = e.target;
        if (!form || !form.classList || !form.classList.contains('cmc-contact-form')) {
            return;
        }
        e.preventDefault();

        var data = new FormData(form);

        // Honeypot — bots fill hidden fields, humans don't. Drop silently.
        if ((data.get('website') || '').toString().trim() !== '') {
            return;
        }

        var to = (form.getAttribute('data-mailto') || '').trim();
        if (!to) return;

        var name    = (data.get('name')    || '').toString().trim();
        var email   = (data.get('email')   || '').toString().trim();
        var subject = (data.get('subject') || '').toString().trim();
        var message = (data.get('message') || '').toString().trim();

        var finalSubject = subject || ('Contact from ' + (name || 'website'));
        var body =
            'Name: '  + name  + '\r\n' +
            'Email: ' + email + '\r\n\r\n' +
            message;

        var url = 'mailto:' + to +
                  '?subject=' + enc(finalSubject) +
                  '&body='    + enc(body);

        var btn = form.querySelector('.cmc-contact-form__submit');
        if (btn) {
            btn.setAttribute('disabled', 'disabled');
            setTimeout(function () { btn.removeAttribute('disabled'); }, 1500);
        }

        window.location.href = url;
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', hydrate, false);
    } else {
        hydrate();
    }
    document.addEventListener('submit', handleSubmit, false);
})();
