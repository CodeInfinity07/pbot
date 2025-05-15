{include file="mheader.tpl"}

<div class="container py-4">
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Ticket Details</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Date Submitted</th>
                            <th>Subject</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Created By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>{$ticket.id}</td>
                            <td>{$ticket.datetime}</td>
                            <td>{$ticket.subject}</td>
                            <td><span class="badge bg-warning">High</span></td>
                            <td>
                                {if $ticket.status == 0}
                                    <span class="badge bg-primary">Open</span>
                                {elseif $ticket.status == 1}
                                    <span class="badge bg-info">In Progress</span>
                                {else}
                                    <span class="badge bg-secondary">Closed</span>
                                {/if}
                            </td>
                            <td>{$ticket.creator}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {if $alert}
    <div class="alert {$alert_class} alert-dismissible fade show" role="alert">
        {$alert_message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    {/if}

    <div class="card mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0">Ticket Replies</h5>
        </div>
        <div class="card-body">
            <ul class="list-unstyled">
                {foreach from=$replies key=key item=value}
                <li class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0">{$value.type}</h6>
                        <small class="text-muted">{$value.datetime}</small>
                    </div>
                    <p class="mb-0">{$value.message}</p>
                </li>
                {if !$smarty.foreach.replies.last}
                <hr>
                {/if}
                {/foreach}
            </ul>
        </div>
    </div>

    {if $ticket.status == 0}
    <div class="alert alert-info" role="alert">
        <h5 class="alert-heading">Support Response</h5>
        <p class="mb-0">One of our Support agents will reply as soon as possible.</p>
    </div>
    {elseif $ticket.type == '1'}
    <div class="card">
        <div class="card-header bg-light">
            <h5 class="mb-0">Write a Reply</h5>
        </div>
        <form method="post">
            <div class="card-body">
                <div class="mb-3">
                    <textarea name="message" rows="5" class="form-control" id="mymce" placeholder="Enter your message here"></textarea>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" name="submit" value="submit" class="btn btn-primary">Send Reply</button>
            </div>
        </form>
    </div>
    {else}
    <div class="alert alert-secondary" role="alert">
        <h5 class="alert-heading">Ticket Closed</h5>
        <p class="mb-0">This ticket has been closed and is no longer active.</p>
    </div>
    {/if}
</div>

{include file="mfooter.tpl"}