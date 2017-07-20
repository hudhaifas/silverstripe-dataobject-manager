<div class="dataobject-page">
    <div class="container" style="margin-bottom: 1.5em;">
        <div class="row" >
            <div class="col-md-4">
                $ObjectSearchForm
            </div>

            <div class="col-md-4"></div>

            <div class="col-md-4">
                <% with Single %>
                    <% include Single_Nav %>
                <% end_with %>
            </div>
        </div>
    </div>

    <% with Single %>
        <% include Single_Info %>

        <div class="dataobject-tabs">
            <% include Single_Tabs %>
        </div>
    <% end_with %>
</div>