<% with Single %>
    <meta property="og:url" content="{$Top.AbsoluteLink($ObjectLink)}" />
    <meta property="og:type" content="article" />
    <meta property="og:title" content="$ObjectTitle" />
    <meta property="og:locale" content="$Top.ContentLocale" />
    
    <% if $ObjectSummary.Count >= 0 %>
        <meta property="og:description" content="<% loop $ObjectSummary %><% if $Title %>$Title:<% end_if %> $Value<% if not Last %><%t DataObjectPage.COMMA %> <% end_if %><% end_loop %>" />
    <% else %>
        <meta property="og:description" content="{$Top.Strip($ObjectSummary)}" />
    <% end_if %>
    
    <% if $ObjectImage %>
        <meta property="og:image" content="{$Top.AbsoluteLink($ObjectImage.PaddedImage(1200,627).Watermark.URL)}" />
    <% else_if ObjectDefaultImage %>
        <meta property="og:image" content="{$Top.AbsoluteLink($ObjectDefaultImage)}" />
    <% else %>
        <meta property="og:image" content="{$Top.AbsoluteLink(dataobject-manager/images/default-image.jpg)}" />
    <% end_if %>
<% end_with %>