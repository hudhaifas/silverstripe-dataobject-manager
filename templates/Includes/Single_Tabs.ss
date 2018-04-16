<% with ObjectTabs %>
    <div class="clearfix">
        <ul class="nav nav-pills nav-loaded">
            <% loop $Me %>
               <li class="nav-item"><a href="#tab{$Pos}" data-toggle="tab" class="nav-link <% if First %>active<% end_if %>">$Title</a></li>
            <% end_loop %>
        </ul>

        <div class="container">
            <div class="tab-content">
                <% loop $Me %>
                    <div id="tab{$Pos}" class="row tab-pane fade <% if First %>in active<% end_if %>">
                        <div class="col-md-12">
                            $Content
                        </div>
                    </div>
                <% end_loop %>
            </div>
        </div>
    </div>
<% end_with %>