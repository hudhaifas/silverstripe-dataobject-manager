<div class="dataobject-page">
    <div class="container" style="margin-bottom: 1.5em;">
        <div class="row" >
            <div class="col-md-4">
                $ObjectSearchForm
            </div>

            <div class="col-md-4"></div>

            <div class="col-md-4">
                <% with Single %>
                    <div class="pull-right">
                        <a href="{$ObjectLink}" class="btn btn-default" title="<%t DataObjectPage.SHOW 'Show' %>"><%t DataObjectPage.SHOW 'Show' %></a>
                    </div>
                <% end_with %>
            </div>
        </div>
    </div>

    <% include Single_Info %>

    <div class="container">
        $ObjectEditForm($Single.ClassName, $Single.ID)
    </div>

</div>