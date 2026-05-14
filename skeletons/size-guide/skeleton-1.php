<?php
/**
 * Size Guide skeleton — static industry-standard chart + 3 AI-filled slots.
 *
 * The table content is intentionally hard-coded:
 *   - Numbers are well-known sizing standards (US apparel), not opinions —
 *     GMC review treats this as factual reference data, not duplicate copy.
 *   - Hard-coding eliminates any AI-hallucination risk on the measurements
 *     (the only field GMC reviewers actually verify against the chart).
 *   - The AI's only creative latitude is the heading + intro + outro,
 *     which vary per cloned site so the wrapper text isn't duplicate
 *     content across the network.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return <<<'SKELETON'
[section label="Size Guide Hero" padding="40px" padding__sm="20px" class="cmc-section cmc-size-guide-hero"]
[row]
[col span="12"]

<div class="cmc-container">

<span class="cmc-eyebrow">{{nganh_hang}}</span>
<h1 class="cmc-page-title" style="text-align:center">{{HEADING}}</h1>

[ux_text class="cmc-lead" font_size="1.05" text_align="center"]
{{INTRO}}
[/ux_text]

</div>

[/col]
[/row]
[/section]

[section label="Size Chart" padding="32px" padding__sm="16px" class="cmc-section cmc-size-guide-chart-wrap"]
[row]
[col span="12"]

<div class="cmc-container">

<h2 class="cmc-section-title" style="text-align:center">Standard Size Chart</h2>

<div class="cmc-size-guide-table-wrap" style="overflow-x:auto;">

<table class="cmc-size-guide-table" style="width:100%; border-collapse:collapse; text-align:center;">
<thead>
<tr>
<th style="padding:10px; border-bottom:2px solid #1d2327;">Size</th>
<th style="padding:10px; border-bottom:2px solid #1d2327;">Chest (in)</th>
<th style="padding:10px; border-bottom:2px solid #1d2327;">Chest (cm)</th>
<th style="padding:10px; border-bottom:2px solid #1d2327;">Waist (in)</th>
<th style="padding:10px; border-bottom:2px solid #1d2327;">Waist (cm)</th>
<th style="padding:10px; border-bottom:2px solid #1d2327;">Hip (in)</th>
<th style="padding:10px; border-bottom:2px solid #1d2327;">Hip (cm)</th>
</tr>
</thead>
<tbody>
<tr>
<td style="padding:10px; border-bottom:1px solid #dcdcde;">XS</td>
<td style="padding:10px; border-bottom:1px solid #dcdcde;">30 – 32</td>
<td style="padding:10px; border-bottom:1px solid #dcdcde;">76 – 81</td>
<td style="padding:10px; border-bottom:1px solid #dcdcde;">24 – 26</td>
<td style="padding:10px; border-bottom:1px solid #dcdcde;">61 – 66</td>
<td style="padding:10px; border-bottom:1px solid #dcdcde;">33 – 35</td>
<td style="padding:10px; border-bottom:1px solid #dcdcde;">84 – 89</td>
</tr>
<tr>
<td style="padding:10px; border-bottom:1px solid #dcdcde;">S</td>
<td style="padding:10px; border-bottom:1px solid #dcdcde;">33 – 35</td>
<td style="padding:10px; border-bottom:1px solid #dcdcde;">84 – 89</td>
<td style="padding:10px; border-bottom:1px solid #dcdcde;">27 – 29</td>
<td style="padding:10px; border-bottom:1px solid #dcdcde;">69 – 74</td>
<td style="padding:10px; border-bottom:1px solid #dcdcde;">36 – 38</td>
<td style="padding:10px; border-bottom:1px solid #dcdcde;">91 – 97</td>
</tr>
<tr>
<td style="padding:10px; border-bottom:1px solid #dcdcde;">M</td>
<td style="padding:10px; border-bottom:1px solid #dcdcde;">36 – 38</td>
<td style="padding:10px; border-bottom:1px solid #dcdcde;">91 – 97</td>
<td style="padding:10px; border-bottom:1px solid #dcdcde;">30 – 32</td>
<td style="padding:10px; border-bottom:1px solid #dcdcde;">76 – 81</td>
<td style="padding:10px; border-bottom:1px solid #dcdcde;">39 – 41</td>
<td style="padding:10px; border-bottom:1px solid #dcdcde;">99 – 104</td>
</tr>
<tr>
<td style="padding:10px; border-bottom:1px solid #dcdcde;">L</td>
<td style="padding:10px; border-bottom:1px solid #dcdcde;">39 – 41</td>
<td style="padding:10px; border-bottom:1px solid #dcdcde;">99 – 104</td>
<td style="padding:10px; border-bottom:1px solid #dcdcde;">33 – 35</td>
<td style="padding:10px; border-bottom:1px solid #dcdcde;">84 – 89</td>
<td style="padding:10px; border-bottom:1px solid #dcdcde;">42 – 44</td>
<td style="padding:10px; border-bottom:1px solid #dcdcde;">107 – 112</td>
</tr>
<tr>
<td style="padding:10px; border-bottom:1px solid #dcdcde;">XL</td>
<td style="padding:10px; border-bottom:1px solid #dcdcde;">42 – 44</td>
<td style="padding:10px; border-bottom:1px solid #dcdcde;">107 – 112</td>
<td style="padding:10px; border-bottom:1px solid #dcdcde;">36 – 38</td>
<td style="padding:10px; border-bottom:1px solid #dcdcde;">91 – 97</td>
<td style="padding:10px; border-bottom:1px solid #dcdcde;">45 – 47</td>
<td style="padding:10px; border-bottom:1px solid #dcdcde;">114 – 119</td>
</tr>
<tr>
<td style="padding:10px;">XXL</td>
<td style="padding:10px;">45 – 47</td>
<td style="padding:10px;">114 – 119</td>
<td style="padding:10px;">39 – 41</td>
<td style="padding:10px;">99 – 104</td>
<td style="padding:10px;">48 – 50</td>
<td style="padding:10px;">122 – 127</td>
</tr>
</tbody>
</table>

</div>

</div>

[/col]
[/row]
[/section]

[section label="Size Guide Footer" padding="32px" padding__sm="16px" class="cmc-section cmc-size-guide-footer"]
[row]
[col span="12"]

<div class="cmc-container">

[ux_text class="cmc-lead" font_size="1.0" text_align="center"]
{{OUTRO}}
[/ux_text]

</div>

[/col]
[/row]
[/section]
SKELETON;
