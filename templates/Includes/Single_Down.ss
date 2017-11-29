<div class="container">
    <% if ObjectRelated %>
        <h4 class="col-md-12"><%t DataObjectPage.ALSO_SEE 'Also See' %></h4>

        <div class="row dataobject-grid">
            <% loop ObjectRelated.Limit(4) %>
                <% if not $isObjectDisabled %>
                    <div class="<% if Up.FiltersList %>col-sm-4<% else %>col-sm-3<% end_if %> col-xs-6 dataobject-item">
                        $ObjectItem
                    </div>
                <% end_if %>
            <% end_loop %>
        </div>
    <% end_if %>
</div>