<% if Results  %>
    <div class="dataobject-hlist">
        <% loop $Results %>
            <% if CanPublicView %>
                <div class="dataobject-item">
                    $ObjectItem
                </div>
            <% end_if %>
        <% end_loop %>
    </div>
<% else %>
    <div class="row">
        <p><%t DataObjectPage.SEARCH_NO_RESULTS 'Sorry, your search query did not return any results.' %></p>
    </div>
<% end_if %>