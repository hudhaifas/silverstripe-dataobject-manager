<div class="container dataobject-page $ExtraClasses">
    <div class="dataobject-page-header">
        <div class="row" style="margin-bottom: 1.5em;">
            <div class="col-md-12">
                $ObjectSearchForm
            </div>

            <div class="col-md-12">
                <sub><%t DataObjectPage.SEARCH_RESULTS_COUNT 'About {value} results' value=$Results.Count%></sub>
            </div>
        </div>
    </div>

    <div class="dataobject-page-content">
        <div class="row">
            <% if IsVerticalList %>
                <div class="col-md-12">
                    <% include List_List %>
                </div>
            <% else_if FiltersList %>
                <div class="col-md-4">
                    <% include List_Side %>
                </div>
                <div class="col-md-8">
                    <% include List_Grid %>
                </div>
            <% else %>
                <div class="col-md-12">
                    <% include List_Grid %>
                </div>
            <% end_if %>
        </div>

        <% if Results  %>
            <div class="">
                <div class="col-md-12">
                    <% with $Results %>
                        <% include List_Paginate %>
                    <% end_with %>
                </div>
            </div>
        <% end_if %>
    </div>
</div>