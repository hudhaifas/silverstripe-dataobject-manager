<% if $ObjectSummary.Count >= 0 %>
    <% loop $ObjectSummary %>
        <p class="dataobject-info">
            <% if $Title %>$Title:<% if BR %><br /><% end_if %><% end_if %> $Value
        </p>
    <% end_loop %>
<% else %>
    $ObjectSummary
<% end_if %>