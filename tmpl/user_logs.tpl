{include file="mheader.tpl"}

<div class="container py-4">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Logs</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-striped">
                    <thead class="table-info">
                        <tr>
                            <th>Title</th>
                            <th>Content</th>
                            <th>Date Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach from=$rows key=key item=value}
                        <tr>
                            <td>{$value.title}</td>
                            <td>{$value.content}</td>
                            <td>{$value.datetime}</td>
                        </tr>
                        {foreachelse}
                        <tr>
                            <td colspan="3" class="text-center">No logs found</td>
                        </tr>
                        {/foreach}
                    </tbody>
                </table>
            </div>
        </div>
        {if $paginator}
        <div class="card-footer">
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center mb-0">
                    {$paginator}
                </ul>
            </nav>
        </div>
        {/if}
    </div>
</div>

{include file="mfooter.tpl"}