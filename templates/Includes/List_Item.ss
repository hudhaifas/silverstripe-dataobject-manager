<div style="height: auto;">
    <a <% if CanView %>href="$ObjectLink"<% end_if %> title="$ObjectTitle">
        <div class="thumbnail text-center col-sm-12 col-xs-4">
            <% include List_Image %>

            <% if CanView %>
                <div class="mask"></div>
            <% end_if %>
        </div>

        <div class="content col-sm-12 col-xs-8 ellipsis">
            <% include Single_Summary_Content %>
        </div>		
    </a>
</div>