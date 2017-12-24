<% with Single %>
    <meta property="og:url" content="{$Top.FullURL($ObjectLink)}" />
    <meta property="og:type" content="article" />
    <meta property="og:title" content="$ObjectTitle" />
    
    <% if $ObjectSummary.Count >= 0 %>
        <meta property="og:description" content="<% loop $ObjectSummary %><% if $Title %>$Title:<% end_if %> $Value<% if not Last %><%t DataObjectPage.COMMA %> <% end_if %><% end_loop %>" />
    <% else %>
        <meta property="og:description" content="{$Top.Strip($ObjectSummary)}" />
    <% end_if %>
    
    <% if $ObjectImage %>
        <meta property="og:image" content="{$Top.FullURL($ObjectImage.PaddedImage(1200,627).Watermark.URL)}" />
    <% else_if ObjectDefaultImage %>
        <meta property="og:image" content="{$Top.FullURL($ObjectDefaultImage)}" />
    <% else %>
        <meta property="og:image" content="{$Top.FullURL(dataobject-manager/images/default-image.jpg)}" />
    <% end_if %>
    <meta property="og:locale" content="$Top.ContentLocale" />
<% end_with %>