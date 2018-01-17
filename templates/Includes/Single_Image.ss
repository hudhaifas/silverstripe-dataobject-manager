<% with Single %>
    <div class="thumbnail text-center imgBox">
        <% if ObjectImage %>
            <a href="$ObjectImage.Watermark.URL" data-lightbox="dataobject-gallery" data-title="{$Title}">
                <img src="$ObjectImage.PaddedImage(256,256).Watermark.URL" alt="{$Title}" class="img-responsive" />
            </a>
        <% else %>
            <% if ObjectDefaultImage %>
                <img src= "$ObjectDefaultImage" alt="{$Title}" class="img-responsive" />
            <% else %>
                <img src= "dataobject-manager/images/default-image.jpg" alt="{$Title}" class="img-responsive" />
            <% end_if %>

            <div class="caption" style="">
                <h4>$ObjectTitle.LimitCharacters(110)</h4>
            </div>
        <% end_if %>
    </div>
<% end_with %>
