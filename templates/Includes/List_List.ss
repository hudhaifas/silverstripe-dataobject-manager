<% if Results  %>
    <% if GridTitle  %>
        <div class="row">
            <div class="col-md-12">
                $GridTitle
            </div>
        </div>
    <% end_if %>

    <div class="row dataobject-grid dataobject-vlist">
        <% loop $Results %>
            <% if CanPublicView %>
                <div class="col-sm-12 dataobject-item">
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