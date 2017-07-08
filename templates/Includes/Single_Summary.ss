<% if $ObjectSummary.Count >= 0 %>
    <% if $Title %><p class="title"><a <% if ObjectLink %>href="$ObjectLink"<% end_if %> title="$Title">$Title.LimitCharacters(50)</a></p><% end_if %>
    
    <% loop $ObjectSummary %>
        <p class="details">
            <% if $Title %>$Title:<% if BR %><br /><% end_if %><% end_if %> $Value</p>
    <% end_loop %>
<% else %>
    $ObjectSummary
<% end_if %>