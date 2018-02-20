<!--Facebook-->
<meta property="fb:app_id" content="{$FbAppId}" /> 
<meta property="og:type" content="article" />
<meta property="og:url" content="{$FullURL($ObjectLink)}" />
<meta property="og:title" content="$SiteConfig.Title | <% if $MetaTitle %>$MetaTitle<% else %>$Title<% end_if %>" />
<meta property="og:locale" content="{$ContentLocale}" />
<meta property="og:image:width" content="1200" />
<meta property="og:image:height" content="627" />
<meta property="og:image" content="{$FullURL($DefaultSocialImage.PaddedImage(1200,627).Watermark.URL)}" />
<meta property="og:description" content="{$DefaultSocialDesc}" />

<!--Twitter-->
<meta property="twitter:site" content="{$TwitterSite}" />
<meta property="twitter:title" content="$SiteConfig.Title | <% if $MetaTitle %>$MetaTitle<% else %>$Title<% end_if %>" />
<meta property="twitter:description" content="{$DefaultSocialDesc}" />
<meta property="twitter:card" content="summary_large_image" />
<meta property="twitter:image" content="{$FullURL($DefaultSocialImage.PaddedImage(1200,627).Watermark.URL)}" />