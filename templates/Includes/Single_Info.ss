<div class="container">
    <div class="row dataobject-header">
        <% if $Single.CanEdit && $Single.ObjectEditableImageName %>
        <div class="dataobject-card card-form" style="display: none;">
            $ImageEditForm($Single.ID)
        </div>
        <% end_if %>

        <div class="dataobject-card card-image">
            <div class="dataobject-picture place-holder" data-url="{$PictureLink($Single.ID)}" data-align="$Align">
                <% include Single_Image_PlaceHolder %>
            </div>

            <div class="dataobject-brief place-holder" data-url="{$SummaryLink($Single.ID)}">
                <% include Single_Summary_PlaceHolder %>
            </div>
        </div>
    </div>   
</div>