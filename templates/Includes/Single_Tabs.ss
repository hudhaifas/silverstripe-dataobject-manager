<% if ObjectTabs %>
    <div class="col-md-12 right-tabs clearfix">
        <ul class="nav nav-tabs">
            <% loop ObjectTabs %>
               <li class="<% if First %>active<% end_if %>"><a href="#tab{$Pos}" data-toggle="tab">$Title</a></li>
            <% end_loop %>
        </ul>

        <div class="tab-content">
            <% loop ObjectTabs %>
                <div id="tab{$Pos}" class="tab-pane fade <% if First %>in active<% end_if %>">
                    <p>$Content</p>
                </div>
            <% end_loop %>
        </div>
    </div>
<% end_if %>