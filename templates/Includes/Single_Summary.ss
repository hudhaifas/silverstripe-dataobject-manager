<% if $ObjectSummary.Count >= 0 %>
    <% if $Title %><p class="dataobject-title"><a <% if ObjectLink %>href="$ObjectLink"<% end_if %> title="$Title">$Title</a></p><% end_if %>
    
    <% loop $ObjectSummary %>
        <p class="dataobject-info">
            <% if $Title %>$Title:<% if BR %><br /><% end_if %><% end_if %> $Value</p>
    <% end_loop %>
<% else %>
    $ObjectSummary
<% end_if %>