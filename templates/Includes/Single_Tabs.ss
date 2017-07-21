<% if ObjectTabs %>
    <div class="clearfix">
        <ul class="nav nav-tabs">
            <% loop ObjectTabs %>
               <li class="<% if First %>active<% end_if %>"><a href="#tab{$Pos}" data-toggle="tab">$Title</a></li>
            <% end_loop %>
        </ul>

        <div class="container">
            <div class="tab-content">
                <% loop ObjectTabs %>
                    <div id="tab{$Pos}" class="row tab-pane fade <% if First %>in active<% end_if %>">
                        <div class="col-md-12">
                            $Content
                        </div>
                    </div>
                <% end_loop %>
            </div>
        </div>
    </div>
<% end_if %>