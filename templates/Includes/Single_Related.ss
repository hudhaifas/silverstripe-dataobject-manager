<% with Single %>
    <div class="container">
        <% if ObjectRelated %>
            <h4 class="col-md-12"><%t DataObjectPage.ALSO_SEE 'Also See' %></h4>

            <div class="row dataobject-grid">
                <% loop ObjectRelated.Limit(4) %>
                    <% if not $IsObjectDisabled %>
                        <div class="col-md-2 col-6 dataobject-item">
                            $ObjectItem
                        </div>
                    <% end_if %>
                <% end_loop %>
            </div>
        <% end_if %>
    </div>
<% end_with %>