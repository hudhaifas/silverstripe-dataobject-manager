<div class="dataobject-page dataobject-edit $ExtraClasses">
    <div class="dataobject-page-header">
        <div class="container" style="margin-bottom: 1.5em;">
            <div class="row" >
                <div class="col-md-4">
                    $ObjectSearchForm
                </div>

                <div class="col-md-4"></div>

                <div class="col-md-4">
                    <% with Single %>
                        <div class="pull-right">
                            <a href="{$ObjectLink}" class="btn btn-secondary" title="<%t DataObjectPage.SHOW 'Show' %>"><%t DataObjectPage.SHOW 'Show' %></a>
                        </div>
                    <% end_with %>
                </div>
            </div>
        </div>

        <% include Single_Info %>
    </div>

    <div class="dataobject-page-content">
        <div class="container">
            $ObjectEditForm($Single.ID)
        </div>
    </div>

</div>