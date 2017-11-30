<script type="application/ld+json">
    $RichSnippets
</script>

<div class="dataobject-page">
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

    <% with Single %>
        <% include Single_Info %>

        <div class="dataobject-tabs place-holder" data-url="{$Up.TabsLink($ID)}">
            <% include Single_Tabs_PlaceHolder %>
        </div>

        <% include Single_Down %>
    <% end_with %>
</div>