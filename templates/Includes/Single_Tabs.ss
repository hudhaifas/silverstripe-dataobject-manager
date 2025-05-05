<% with ObjectTabs.Sort('Rank DESC') %>
    <div class="clearfix">
        <ul class="nav nav-pills justify-content-center nav-loaded">
            <% loop $Me %>
               <li class="nav-item">
                   <a href="#tab{$Pos}"
                      data-toggle="pill"
                      class="nav-link tab-link <% if $IsFirst %>active show<% end_if %>"
                      data-tab="$Key"
                      data-object-id="$Top.Single.ID"
                      data-object-class="$Top.Single.ClassName">
                       $Title
                   </a>
               </li>
            <% end_loop %>
        </ul>

        <div class="container">
            <div class="tab-content">
                <% loop $Me %>
                    <div id="tab{$Pos}" class="container tab-pane fade <% if $IsFirst %>active show<% end_if %>">
                        <div id="$Key-content" class="col-12 tab-pane-content">
                            <% if $IsFirst %>
                                <span class="loader"></span>
                            <% end_if %>
                        </div>
                    </div>
                <% end_loop %>
            </div>
        </div>
    </div>
<% end_with %>
