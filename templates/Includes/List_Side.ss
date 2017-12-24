<% loop FiltersList %>
    <% if CanView %>
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
