<% with ObjectTabs %>
    <div class="clearfix">
        <ul class="nav nav-pills justify-content-center nav-loaded">
            <% loop $Me %>
               <li class="nav-item"><a href="#tab{$Pos}" data-toggle="pill" class="nav-link tab-link <% if First %>active show<% end_if %>">$Title</a></li>
            <% end_loop %>
        </ul>

        <div class="container">
            <div class="tab-content">
                <% loop $Me %>
                    <div id="tab{$Pos}" class="container tab-pane fade <% if First %>active show<% end_if %>">
                        <div class="col-12">
                            $Content
                        </div>
                    </div>
                <% end_loop %>
            </div>
        </div>
    </div>
<% end_with %>