<div>
    <a <% if ObjectLink %>href="$ObjectLink"<% end_if %> title="$ObjectTitle">
        <div class="thumbnail text-center col-sm-12 col-xs-4 dataobject-image">
            <% include List_Image %>

            <% if ObjectLink %>
                <div class="mask">
                    <div class="info"><%t DataObjectPage.MORE_ABOUT 'More' %></div>
                </div>
            <% end_if %>
        </div>


        <div class="content col-sm-12 col-xs-8 dataobject-summary">
            <% include Single_Summary %>
        </div>		
    </a>
</div>