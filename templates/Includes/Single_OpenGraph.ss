<% with Single %>
    <!--Facebook-->
    <meta property="fb:app_id" content="{$Top.FbAppId}" /> 
    <meta property="og:type" content="article" />
    <meta property="og:url" content="{$Top.FullURL($ObjectLink)}" />
    <meta property="og:title" content="$SiteConfig.Title | $ObjectTitle" />
    <meta property="og:locale" content="{$Top.ContentLocale}" />
    <meta property="og:image:width" content="1200" />
    <meta property="og:image:height" content="627" />
    <% if $ObjectImage %>
        <meta property="og:image" content="{$Top.FullURL($ObjectImage.PaddedImage(1200,627).Watermark.URL)}" />
    <% else %>
        <meta property="og:image" content="{$Top.FullURL($Top.DefaultSocialImage.PaddedImage(1200,627).Watermark.URL)}" />
    <% end_if %>    

    <% if $SocialDescription %>
        <meta property="og:description" content="{$SocialDescription.Summary(300)}" />
    <% else %>
        <meta property="og:description" content="{$Top.DefaultSocialDesc}" />
    <% end_if %>
    
    <!--Twitter-->
    <meta property="twitter:site" content="{$Top.TwitterSite}" />
    <meta property="twitter:title" content="$SiteConfig.Title | $ObjectTitle" />
    <% if $SocialDescription %>
        <meta property="twitter:description" content="{$SocialDescription.Summary(300)}" />
    <% else %>
        <meta property="twitter:description" content="{$Top.DefaultSocialDesc}" />
    <% end_if %>
    
    <meta property="twitter:card" content="summary_large_image" />
    <% if $ObjectImage %>
        <meta property="twitter:image" content="{$Top.FullURL($ObjectImage.PaddedImage(1200,627).Watermark.URL)}" />
    <% else %>
        <meta property="twitter:image" content="{$Top.FullURL($Top.DefaultSocialImage.PaddedImage(1200,627).Watermark.URL)}" />
    <% end_if %>    
<% end_with %>