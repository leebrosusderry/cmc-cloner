<?php
/**
 * Live Chat — minimal floating contact panel for GMC trust signal.
 *
 * Ships a small chat-style form fixed to the bottom-right of every
 * front-end page. The sole purpose of this widget is to look responsive
 * to a GMC reviewer auditing the site — it is NOT a real support
 * channel. Submissions are validated for shape (nonce + honeypot + non-
 * empty fields) and then *acknowledged with a fake success notification*
 * — no email is sent, nothing is persisted, no third-party service is
 * called. This keeps cloning trivial (zero SMTP/mailbox setup per site)
 * while still presenting a working chat experience to GMC's automated
 * and human reviewers.
 *
 * If a real support channel is needed in the future, swap the
 * fake-success branch in handle_submit() for wp_mail() + a recipient
 * lookup; everything else (markup, panel JS, validation) stays the same.
 *
 * Design choices:
 *   - No external service (Tawk/Crisp) → no branding, no GDPR cookie.
 *   - No DB table, no email — pure trust-signal UI.
 *   - Spam protection = honeypot field only (cheap, JS-free).
 *   - Style sourced from var(--nt-primary) injected by CMC_Public →
 *     panel re-skins automatically when the shop's primary color changes.
 *   - Header strings (shop name, business hours) come from existing
 *     Settings keys (ten_web, gio_lam_viec) so cloning a site needs no
 *     LiveChat-specific config.
 *
 * Hook surface:
 *   wp_footer (priority 20)              — print HTML panel + inline CSS/JS
 *   wp_ajax_cmc_livechat_submit          — logged-in submit handler
 *   wp_ajax_nopriv_cmc_livechat_submit   — anonymous submit handler
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class CMC_LiveChat {

    public const AJAX_ACTION  = 'cmc_livechat_submit';
    public const NONCE_ACTION = 'cmc_livechat_nonce';

    public static function init(): void {
        add_action( 'wp_footer',                              [ self::class, 'print_panel' ], 20 );
        add_action( 'wp_ajax_'        . self::AJAX_ACTION,    [ self::class, 'handle_submit' ] );
        add_action( 'wp_ajax_nopriv_' . self::AJAX_ACTION,    [ self::class, 'handle_submit' ] );
    }

    /**
     * Render the floating chat button + collapsed panel + inline CSS/JS
     * into the front-end footer. Skips admin and feed contexts.
     */
    public static function print_panel(): void {
        if ( is_admin() || is_feed() ) {
            return;
        }
        if ( ! class_exists( 'CMC_Settings' ) ) {
            return;
        }

        $settings = CMC_Settings::get();

        // Master toggle — when off, render nothing at all (no button,
        // no panel markup, no inline CSS/JS).
        if ( empty( $settings['livechat_enabled'] ) ) {
            return;
        }

        $company   = (array) ( $settings['company_info'] ?? [] );
        $shop_name = (string) ( $company['ten_web'] ?? '' );
        if ( $shop_name === '' ) {
            $shop_name = (string) get_bloginfo( 'name' );
        }

        $hours = (string) CMC_Settings::service_commitment( 'gio_lam_viec' );
        if ( $hours === '' ) {
            $hours = 'Mon – Fri, 9:00 AM – 5:00 PM';
        }

        // Optional button label — empty = circular icon-only (default).
        // Non-empty switches the button into a pill shape with text.
        $button_label = trim( (string) ( $settings['livechat_button_label'] ?? '' ) );

        $nonce    = wp_create_nonce( self::NONCE_ACTION );
        $ajax_url = admin_url( 'admin-ajax.php' );
        ?>
<style id="cmc-livechat-css">
.cmc-chat-fab{position:fixed;right:24px;bottom:24px;min-width:56px;height:56px;border-radius:28px;background:var(--nt-primary,#1a1a1a);color:#fff;display:inline-flex;align-items:center;justify-content:center;gap:8px;cursor:pointer;box-shadow:0 4px 16px rgba(0,0,0,.18);border:0;z-index:9999;transition:transform .2s ease,box-shadow .2s ease;padding:0 18px;font-family:inherit;font-size:.95rem;font-weight:600;line-height:1;}
.cmc-chat-fab.is-icon-only{width:56px;padding:0;}
.cmc-chat-fab:hover{transform:scale(1.06);box-shadow:0 6px 22px rgba(0,0,0,.24);}
.cmc-chat-fab svg{width:26px;height:26px;fill:#fff;flex-shrink:0;}
.cmc-chat-panel{position:fixed;right:24px;bottom:96px;width:340px;max-width:calc(100vw - 48px);background:#fff;border-radius:16px;box-shadow:0 12px 40px rgba(0,0,0,.18);overflow:hidden;display:none;flex-direction:column;font-family:inherit;z-index:9999;color:#1a1a1a;}
.cmc-chat-panel.is-open{display:flex;}
.cmc-chat-panel__header{background:var(--nt-primary,#1a1a1a);color:#fff;padding:18px 20px;position:relative;}
.cmc-chat-panel__title{margin:0 0 4px;font-size:1.05rem;font-weight:700;color:#fff;line-height:1.3;}
.cmc-chat-panel__hours{margin:0;font-size:.78rem;opacity:.92;display:flex;align-items:center;gap:6px;color:#fff;}
.cmc-chat-panel__hours::before{content:"";width:8px;height:8px;border-radius:50%;background:#27ae60;display:inline-block;flex-shrink:0;}
.cmc-chat-panel__close{position:absolute;top:14px;right:14px;background:transparent;border:0;color:#fff;font-size:1.6rem;cursor:pointer;line-height:1;padding:4px 8px;opacity:.85;}
.cmc-chat-panel__close:hover{opacity:1;}
.cmc-chat-panel__body{padding:18px 20px 20px;}
.cmc-chat-panel__field{margin-bottom:12px;}
.cmc-chat-panel__field label{display:block;font-size:.78rem;color:#555;margin-bottom:5px;font-weight:600;}
.cmc-chat-panel__field input,.cmc-chat-panel__field textarea{width:100%;padding:9px 12px;border:1px solid #d0d0d0;border-radius:8px;font-size:.92rem;font-family:inherit;background:#fff;box-sizing:border-box;color:#1a1a1a;}
.cmc-chat-panel__field input:focus,.cmc-chat-panel__field textarea:focus{outline:none;border-color:var(--nt-primary,#1a1a1a);}
.cmc-chat-panel__field textarea{min-height:90px;resize:vertical;}
.cmc-chat-panel__honey{position:absolute !important;left:-9999px !important;width:1px !important;height:1px !important;opacity:0 !important;}
.cmc-chat-panel__send{width:100%;background:var(--nt-primary,#1a1a1a);color:#fff;border:0;padding:11px 16px;border-radius:8px;font-size:.95rem;font-weight:600;cursor:pointer;font-family:inherit;}
.cmc-chat-panel__send:hover{filter:brightness(.92);}
.cmc-chat-panel__send:disabled{opacity:.6;cursor:not-allowed;}
.cmc-chat-panel__msg{margin:10px 0 0;font-size:.85rem;text-align:center;min-height:1em;}
.cmc-chat-panel__msg.is-success{color:#27ae60;}
.cmc-chat-panel__msg.is-error{color:#c0392b;}
@media (max-width: 480px){.cmc-chat-panel{right:12px;left:12px;width:auto;bottom:84px;}.cmc-chat-fab{right:16px;bottom:16px;}}
</style>

<button type="button" class="cmc-chat-fab<?php echo $button_label === '' ? ' is-icon-only' : ''; ?>" id="cmc-chat-fab" aria-label="Open chat">
  <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H5.17L4 17.17V4h16v12z"/><path d="M7 9h10v2H7zm0 3h7v2H7z"/></svg>
  <?php if ( $button_label !== '' ) : ?><span><?php echo esc_html( $button_label ); ?></span><?php endif; ?>
</button>

<div class="cmc-chat-panel" id="cmc-chat-panel" role="dialog" aria-labelledby="cmc-chat-title" aria-hidden="true">
  <div class="cmc-chat-panel__header">
    <button type="button" class="cmc-chat-panel__close" id="cmc-chat-close" aria-label="Close chat">×</button>
    <h3 class="cmc-chat-panel__title" id="cmc-chat-title">Chat with <?php echo esc_html( $shop_name ); ?></h3>
    <p class="cmc-chat-panel__hours"><?php echo esc_html( $hours ); ?></p>
  </div>
  <div class="cmc-chat-panel__body">
    <form id="cmc-chat-form" novalidate>
      <div class="cmc-chat-panel__field">
        <label for="cmc-chat-name">Your name</label>
        <input type="text" id="cmc-chat-name" name="name" maxlength="100" required>
      </div>
      <div class="cmc-chat-panel__field">
        <label for="cmc-chat-email">Email</label>
        <input type="email" id="cmc-chat-email" name="email" maxlength="255" required>
      </div>
      <div class="cmc-chat-panel__field">
        <label for="cmc-chat-message">How can we help?</label>
        <textarea id="cmc-chat-message" name="message" maxlength="2000" required></textarea>
      </div>
      <input type="text" name="website" class="cmc-chat-panel__honey" tabindex="-1" autocomplete="off" aria-hidden="true">
      <button type="submit" class="cmc-chat-panel__send" id="cmc-chat-send">Send message</button>
      <p class="cmc-chat-panel__msg" id="cmc-chat-msg" aria-live="polite"></p>
    </form>
  </div>
</div>

<script id="cmc-livechat-js">
(function(){
  var fab = document.getElementById('cmc-chat-fab');
  var panel = document.getElementById('cmc-chat-panel');
  var closeBtn = document.getElementById('cmc-chat-close');
  var form = document.getElementById('cmc-chat-form');
  var sendBtn = document.getElementById('cmc-chat-send');
  var msg = document.getElementById('cmc-chat-msg');
  if(!fab || !panel || !form){ return; }

  function open(){ panel.classList.add('is-open'); panel.setAttribute('aria-hidden','false'); }
  function close(){ panel.classList.remove('is-open'); panel.setAttribute('aria-hidden','true'); }

  fab.addEventListener('click', open);
  closeBtn.addEventListener('click', close);

  form.addEventListener('submit', function(e){
    e.preventDefault();
    msg.className = 'cmc-chat-panel__msg';
    msg.textContent = '';
    sendBtn.disabled = true;
    sendBtn.textContent = 'Sending…';

    var data = new FormData(form);
    data.append('action', '<?php echo esc_js( self::AJAX_ACTION ); ?>');
    data.append('nonce',  '<?php echo esc_js( $nonce ); ?>');

    fetch('<?php echo esc_js( $ajax_url ); ?>', { method:'POST', body:data, credentials:'same-origin' })
      .then(function(r){ return r.json().catch(function(){ return null; }); })
      .then(function(j){
        sendBtn.disabled = false;
        sendBtn.textContent = 'Send message';
        if(j && j.success){
          msg.className = 'cmc-chat-panel__msg is-success';
          msg.textContent = (j.data && j.data.message) ? j.data.message : 'Thanks! We will reply soon.';
          form.reset();
          setTimeout(function(){ close(); msg.textContent=''; }, 3500);
        } else {
          msg.className = 'cmc-chat-panel__msg is-error';
          msg.textContent = (j && j.data && j.data.message) ? j.data.message : 'Could not send. Please try again.';
        }
      })
      .catch(function(){
        sendBtn.disabled = false;
        sendBtn.textContent = 'Send message';
        msg.className = 'cmc-chat-panel__msg is-error';
        msg.textContent = 'Network error. Please try again.';
      });
  });
})();
</script>
        <?php
    }

    /**
     * AJAX handler. Validates nonce + honeypot + required fields and
     * always returns a friendly success message — submissions are NOT
     * forwarded anywhere. This is intentional: the widget exists purely
     * as a GMC trust signal (review-time UX), not a real support
     * channel, so cloning a site requires zero SMTP/mailbox setup. If a
     * real support channel is needed later, replace the success branch
     * with wp_mail() + recipient lookup.
     */
    public static function handle_submit(): void {
        if ( ! check_ajax_referer( self::NONCE_ACTION, 'nonce', false ) ) {
            wp_send_json_error( [ 'message' => 'Invalid request. Please refresh and try again.' ], 403 );
        }

        // Master toggle — reject submits if an admin disabled the panel
        // after a stale page was already loaded.
        $settings = CMC_Settings::get();
        if ( empty( $settings['livechat_enabled'] ) ) {
            wp_send_json_error( [ 'message' => 'Live Chat is currently disabled.' ], 403 );
        }

        // Honeypot — bots fill every visible field, including this
        // hidden one. Pretend success so the bot doesn't retry with a
        // different payload.
        if ( ! empty( $_POST['website'] ) ) {
            wp_send_json_success( [ 'message' => 'Thanks! We have received your message and will reply soon.' ] );
        }

        $name    = isset( $_POST['name'] )    ? sanitize_text_field( wp_unslash( $_POST['name'] ) )           : '';
        $email   = isset( $_POST['email'] )   ? sanitize_email( wp_unslash( $_POST['email'] ) )                : '';
        $message = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) )     : '';

        if ( $name === '' || $email === '' || $message === '' ) {
            wp_send_json_error( [ 'message' => 'Please fill in all fields.' ], 400 );
        }
        if ( ! is_email( $email ) ) {
            wp_send_json_error( [ 'message' => 'Please enter a valid email address.' ], 400 );
        }
        if ( strlen( $message ) < 5 ) {
            wp_send_json_error( [ 'message' => 'Message is too short.' ], 400 );
        }

        // Fake success — no email, no DB. Pure trust-signal UX.
        wp_send_json_success( [
            'message' => 'Thanks! We have received your message and will reply as soon as possible.',
        ] );
    }
}
