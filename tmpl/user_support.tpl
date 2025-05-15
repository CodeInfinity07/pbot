{include file="mheader.tpl"}

<div class="page-wrapper">
    <div class="container-fluid py-4">
        {if $alert}
        <div class="alert {$alert_class} alert-dismissible fade show" role="alert">
            {$alert_message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        {/if}

        <div class="card mb-4">
            <h5 class="card-header bg-primary text-white">Support</h5>
            <div class="card-body">
                <h4 class="card-title mb-4">Support Ticket List</h4>

                <div class="row g-4 mb-4">
                    <div class="col-md-6 col-lg-3">
                        <div class="card bg-info text-white">
                            <div class="card-body text-center">
                                <h2 class="mb-1">{$total_tickets}</h2>
                                <p class="mb-0">Total Tickets</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body text-center">
                                <h2 class="mb-1">{$responded_tickets}</h2>
                                <p class="mb-0">Responded</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="card bg-success text-white">
                            <div class="card-body text-center">
                                <h2 class="mb-1">{$resolved_tickets}</h2>
                                <p class="mb-0">Resolved</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body text-center">
                                <h2 class="mb-1">{$opened_tickets}</h2>
                                <p class="mb-0">Opened</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID #</th>
                                <th>Subject</th>
                                <th>Status</th>
                                <th>Priority</th>
                                <th>Datetime</th>
                            </tr>
                        </thead>
                        <tbody>
                            {foreach from=$tickets key=key item=value}
                            <tr>
                                <td><a href="ticket?id={$value.id}" class="text-primary">{$value.id}</a></td>
                                <td><a href="ticket?id={$value.id}" class="text-primary">{$value.subject}</a></td>
                                <td>
                                    {if $value.status == 0}<span class="badge bg-primary">Open</span>{/if}
                                    {if $value.status == 1}<span class="badge bg-warning">Responded</span>{/if}
                                    {if $value.status == 2}<span class="badge bg-success">Resolved</span>{/if}
                                </td>
                                <td>{$value.priority}</td>
                                <td>{$value.datetime}</td>
                            </tr>
                            {foreachelse}
                            <tr>
                                <td colspan="5" class="text-center">No Tickets found</td>
                            </tr>
                            {/foreach}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {if $opened_tickets < 5}
        <div class="card">
            <h5 class="card-header bg-primary text-white">Open a New Support Ticket</h5>
            <div class="card-body">
                <form method="post" action="support">
                    <div class="mb-3">
                        <label for="subject" class="form-label">Subject</label>
                        <input type="text" id="subject" name="subject" class="form-control" placeholder="Enter ticket subject" required>
                    </div>
                    <div class="mb-3">
                        <label for="priority" class="form-label">Priority</label>
                        <select id="priority" name="priority" class="form-select">
                            <option value="Normal">Normal</option>
                            <option value="High">High</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="message" class="form-label">Your Message</label>
                        <textarea id="message" name="message" rows="5" class="form-control" placeholder="Enter your message"></textarea>
                    </div>
                    {include file="captcha.tpl" action="support"}
                    <div class="text-end">
                        <button type="submit" name="submit" value="submit" class="btn btn-primary">Open Ticket</button>
                    </div>
                </form>
            </div>
        </div>
        {/if}
    </div>
</div>

{include file="mfooter.tpl"}