<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return <<<'SKELETON'
[section label="FAQ" padding="36px" padding__sm="20px" class="cmc-section cmc-faq-section cmc-variant--minimal"]
[row]
[col span="12"]

<div class="cmc-container cmc-container--narrow">

<h1 class="cmc-page-title">{{HEADING}}</h1>

[ux_text class="cmc-lead"]
{{INTRO}}
[/ux_text]

<h2>Orders &amp; Checkout</h2>

<h3>{{Q_ORDER_1}}</h3>
{{A_ORDER_1}}

<h3>{{Q_ORDER_2}}</h3>
{{A_ORDER_2}}

<h3>{{Q_ORDER_3}}</h3>
{{A_ORDER_3}}

<h2>Shipping &amp; Delivery</h2>

<h3>{{Q_SHIP_1}}</h3>
{{A_SHIP_1}}

<h3>{{Q_SHIP_2}}</h3>
{{A_SHIP_2}}

<h3>{{Q_SHIP_3}}</h3>
{{A_SHIP_3}}

<h2>Returns &amp; Refunds</h2>

<h3>{{Q_RETURN_1}}</h3>
{{A_RETURN_1}}

<h3>{{Q_RETURN_2}}</h3>
{{A_RETURN_2}}

<h3>{{Q_RETURN_3}}</h3>
{{A_RETURN_3}}

<h2>Payments &amp; Security</h2>

<h3>{{Q_PAY_1}}</h3>
{{A_PAY_1}}

<h3>{{Q_PAY_2}}</h3>
{{A_PAY_2}}

<hr>

<h2>Contact</h2>
{{CONTACT_BLOCK}}

</div>

[/col]
[/row]
[/section]
SKELETON;
