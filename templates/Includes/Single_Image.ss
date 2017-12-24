<% with Single %>
    <div class="thumbnail text-center imgBox">
        <% if ObjectImage && not isObjectDisabled %>
            <img src="$ObjectImage.PaddedImage(256,256).Watermark.URL" data-origin="$ObjectImage.Watermark.URL">
        <% else %>
            <% if ObjectDefaultImage %>
                <img src= "$ObjectDefaultImage" alt="" class="img-responsive zoom-img" />
            <% else %>
                <img src= "dataobject-manager/images/default-image.jpg" alt="" class="img-responsive zoom-img" />
            <% end_if %>

            <div class="caption" style="">
                <h4>$ObjectTitle.LimitCharacters(110)</h4>
            </div>
        <% end_if %>
    </div>
<% end_with %>
