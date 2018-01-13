<% with Single %>
    <!--Facebook-->
    <meta property="fb:app_id" content="{$Top.FbAppId}" /> 
    <meta property="og:type" content="article" />
    <meta property="og:url" content="{$Top.FullURL($ObjectLink)}" />
    <meta property="og:title" content="$SiteConfig.Title | $ObjectTitle" />
    <meta property="og:locale" content="{$Top.ContentLocale}" />
    <% if $ObjectImage %>
        <meta property="og:image" content="{$Top.FullURL($ObjectImage.PaddedImage(1200,627).Watermark.URL)}" />
    <% else %>
        <meta property="og:image" content="{$Top.ThemedURL(/images/social-1200x627.png)}" />
    <% end_if %>    

    <% if $SocialDescription %>
        <meta property="og:description" content="{$SocialDescription}" />
    <% else_if $ObjectSummary.Count >= 0 %>
        <meta property="og:description" content="<% loop $ObjectSummary %><% if $Title %>$Title:<% end_if %> $Value<% if not Last %><%t DataObjectPage.COMMA %> <% end_if %><% end_loop %>" />
    <% else %>
        <meta property="og:description" content="{$Top.Strip($ObjectSummary)}" />
    <% end_if %>
    
    <!--Twitter-->
    <meta property="twitter:title" content="$SiteConfig.Title | $ObjectTitle" />
    <% if $SocialDescription %>
        <meta property="twitter:description" content="{$SocialDescription}" />
    <% else_if $ObjectSummary.Count >= 0 %>
        <meta property="twitter:description" content="<% loop $ObjectSummary %><% if $Title %>$Title:<% end_if %> $Value<% if not Last %><%t DataObjectPage.COMMA %> <% end_if %><% end_loop %>" />
    <% else %>
        <meta property="twitter:description" content="$SocialDescription" />
    <% end_if %>
    
    <meta property="twitter:card" content="summary_large_image" />
    <% if $ObjectImage %>
        <meta property="twitter:image" content="{$Top.FullURL($ObjectImage.PaddedImage(1200,627).Watermark.URL)}" />
    <% else %>
        <meta property="twitter:image" content="{$Top.ThemedURL(/images/social-1200x627.png)}" />
    <% end_if %>    
    <meta property="twitter:site" content="{$Top.TwitterSite}" />
<% end_with %>