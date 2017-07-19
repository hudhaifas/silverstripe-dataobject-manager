<div class="dataobject-related dataobject-item col-xs-12 col-sm-6 col-md-12">
    <div>
        <a <% if ObjectLink %>href="$ObjectLink"<% end_if %> title="$ObjectTitle">
            <div class="thumbnail text-center col-xs-3 col-sm-4">
                <% include List_Image %>

                <% if ObjectLink %>
                    <div class="mask"></div>
                <% end_if %>
            </div>


            <div class="content col-xs-9 col-sm-8 dataobject-summary">
                <% include Single_Summary %>
            </div>		
        </a>
    </div>
</div>