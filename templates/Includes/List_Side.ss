<% if FiltersList %>
    <div class="col-md-4">
        <% loop FiltersList %>
            <% if not $isObjectDisabled %>
                <div class="dataobject-side">
                    <h5 class="side-menu">$Title</h5>

                    <ul>
                        <% loop Items.Limit(8) %>
                            <li class="cat-item"><a href="$Link" title="{$Title}">$Title</a></li>
                        <% end_loop %>
                    </ul>
                </div>
            <% end_if %>
        <% end_loop %>
    </div>
<% end_if %>
