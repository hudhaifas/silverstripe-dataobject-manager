<% if ObjectTabs %>
    <div class="clearfix">
        <ul class="nav nav-tabs">
            <% loop ObjectTabs %>
               <li class="<% if First %>active<% end_if %>"><a href="#tab{$Pos}" data-toggle="tab">$Title</a></li>
            <% end_loop %>
        </ul>

        <div class="tab-content">
            <% loop ObjectTabs %>
                <div id="tab{$Pos}" class="container tab-pane fade <% if First %>in active<% end_if %>">
                    $Content
                </div>
            <% end_loop %>
        </div>
    </div>
<% end_if %>