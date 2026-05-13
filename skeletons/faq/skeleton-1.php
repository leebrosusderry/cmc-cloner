<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return <<<'SKELETON'
[section label="FAQ" padding="24px" padding__sm="16px" class="cmc-section cmc-faq-section"]
[row]
[col span="12"]

<div class="cmc-container">

<h1 class="cmc-page-title" style="text-align:center;margin-bottom:8px">{{HEADING}}</h1>

[ux_text text_align="center" font_size="1.05" class="cmc-lead"]
{{INTRO}}
[/ux_text]

[divider align="center" width="60px" margin="12px"]

<h2>Orders &amp; Checkout</h2>

[accordion title="Orders &amp; Checkout"]

[accordion-item title="{{Q_ORDER_1}}"]
{{A_ORDER_1}}
[/accordion-item]

[accordion-item title="{{Q_ORDER_2}}"]
{{A_ORDER_2}}
[/accordion-item]

[accordion-item title="{{Q_ORDER_3}}"]
{{A_ORDER_3}}
[/accordion-item]

[/accordion]

<h2>Shipping &amp; Delivery</h2>

[accordion title="Shipping &amp; Delivery"]

[accordion-item title="{{Q_SHIP_1}}"]
{{A_SHIP_1}}
[/accordion-item]

[accordion-item title="{{Q_SHIP_2}}"]
{{A_SHIP_2}}
[/accordion-item]

[accordion-item title="{{Q_SHIP_3}}"]
{{A_SHIP_3}}
[/accordion-item]

[/accordion]

<h2>Returns &amp; Refunds</h2>

[accordion title="Returns &amp; Refunds"]

[accordion-item title="{{Q_RETURN_1}}"]
{{A_RETURN_1}}
[/accordion-item]

[accordion-item title="{{Q_RETURN_2}}"]
{{A_RETURN_2}}
[/accordion-item]

[accordion-item title="{{Q_RETURN_3}}"]
{{A_RETURN_3}}
[/accordion-item]

[/accordion]

<h2>Payments &amp; Security</h2>

[accordion title="Payments &amp; Security"]

[accordion-item title="{{Q_PAY_1}}"]
{{A_PAY_1}}
[/accordion-item]

[accordion-item title="{{Q_PAY_2}}"]
{{A_PAY_2}}
[/accordion-item]

[/accordion]

[gap height="6px"]

<div class="cmc-contact-banner" style="text-align:center;padding:32px 24px;border:1px solid #eee;border-radius:8px;background:rgb(250,250,250)">
<h3>Didn&#039;t find your answer?</h3>
{{CONTACT_BLOCK}}
</div>

</div>

[/col]
[/row]
[/section]
SKELETON;
