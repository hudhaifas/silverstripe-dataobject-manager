<div class="dataobject-page dataobject-show">
    <div class="dataobject-page-header">
        <div class="container" style="margin-bottom: 1.5em;">
            <div class="row" >
                <div class="col-md-4">
                    $ObjectSearchForm
                </div>

                <div class="col-md-4"></div>

                <div class="col-md-4">
                    <% with Single %>
                        <% if ObjectNav %>
                            $ObjectNav
                        <% else %>
                            <% include Single_Nav %>
                        <% end_if %>
                    <% end_with %>
                </div>
            </div>
        </div>

        <% include Single_Info %>
    </div>

    <div class="dataobject-page-content">
        <div class="dataobject-tabs place-holder" data-url="{$TabsLink($Single.ID)}">
                <% include Single_Tabs_PlaceHolder %>
            </div>

        <div class="dataobject-related place-holder" data-url="{$RelatedLink($Single.ID)}">
            <% include Single_Related_PlaceHolder %>
        </div>
    </div>
</div>