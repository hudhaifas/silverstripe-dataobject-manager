<% if $MoreThanOnePage %>
    <ul class="pagination pull-right">
        <% if $NotFirstPage %>
            <li><a href="$PrevLink" class="previous"><i class="glyphicon glyphicon-chevron-left"></i></a></li>
        <% end_if %>

        <% loop $PaginationSummary(3)  %>
            <% if $CurrentBool %>
                <li class="active"><a>$PageNum</a></li>
            <% else %>
                <% if $Link %>
                    <li><a href="$Link">$PageNum</a></li>
                <% else %>
                    <li><a>...</a></li>
                <% end_if %>
            <% end_if %>
        <% end_loop %>
        
        <% if $NotLastPage %>
        <li><a href="$NextLink" class="next"><i class="glyphicon glyphicon-chevron-right"></i></a></li>
        <% end_if %>
    </ul>
<% end_if %>