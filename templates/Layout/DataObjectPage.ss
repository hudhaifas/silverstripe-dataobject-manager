<div class="container dataobject-page">
    <div class="row" style="margin-bottom: 1.5em;">
        <div class="col-md-12">
            $ObjectSearchForm
        </div>
        <div class="col-md-12">
            <sub><%t DataObjectPage.SEARCH_RESULTS_COUNT 'About {value} results' value=$Results.Count%></sub>
        </div>
    </div>

    <div class="row">
        <% include List_Side %>

        <% include List_Grid %>
    </div>

    <% if Results  %>
        <div class="row">
            <div class="col-md-12">
                <% with $Results %>
                    <% include List_Paginate %>
                <% end_with %>
            </div>
        </div>
    <% end_if %>
</div>