{include file="mheader.tpl"}

<div class="container py-4">
    {if $alert}
    <div class="alert {$alert_class} alert-dismissible fade show" role="alert">
        {$alert_message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    {/if}

    {if !$userinfo['kyc']}
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">KYC Verification</h5>
        </div>
        <div class="card-body">
            <form method="post" enctype="multipart/form-data">
                <div class="row g-3">
                    {if $settings.kyc.name}
                    <div class="col-md-6">
                        <label for="name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    {/if}

                    {if $settings.kyc.document}
                    <div class="col-md-6">
                        <label for="document" class="form-label">Document Type</label>
                        <select class="form-select" id="document" name="document" required>
                            {if $settings.kyc.document.license}
                            <option value="License">License</option>
                            {/if}
                            {if $settings.kyc.document.idcard}
                            <option value="idcard">ID Card</option>
                            {/if}
                            {if $settings.kyc.document.passport}
                            <option value="passport">Passport</option>
                            {/if}
                        </select>
                    </div>
                    {/if}

                    {if $settings.kyc.front}
                    <div class="col-md-6">
                        <label for="front_image" class="form-label">Upload Front Side</label>
                        <input type="file" class="form-control" id="front_image" name="front_image" accept="image/*" required>
                    </div>
                    {/if}

                    {if $settings.kyc.back}
                    <div class="col-md-6">
                        <label for="back_image" class="form-label">Upload Back Side</label>
                        <input type="file" class="form-control" id="back_image" name="back_image" accept="image/*" required>
                    </div>
                    {/if}

                    {if $settings.kyc.number}
                    <div class="col-md-6">
                        <label for="docnumber" class="form-label">Document Number</label>
                        <input type="text" class="form-control" id="docnumber" name="docnumber" required>
                    </div>
                    {/if}

                    {if $settings.kyc.phone}
                    <div class="col-md-6">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="text" class="form-control" id="phone" name="phone" required>
                    </div>
                    {/if}

                    {if $settings.kyc.country}
                    <div class="col-md-6">
                        <label for="country" class="form-label">Country</label>
                        <input type="text" class="form-control" id="country" name="country" required>
                    </div>
                    {/if}

                    {if $settings.kyc.address}
                    <div class="col-md-6">
                        <label for="address" class="form-label">Address</label>
                        <input type="text" class="form-control" id="address" name="address" required>
                    </div>
                    {/if}

                    {if $settings.kyc.bill}
                    <div class="col-md-6">
                        <label for="address_image" class="form-label">Upload Bill for Address Verification</label>
                        <input type="file" class="form-control" id="address_image" name="address_image" accept="image/*" required>
                    </div>
                    {/if}
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary" name="submit">Submit KYC</button>
                </div>
            </form>
        </div>
    </div>
    {else}
    <div class="alert alert-info" role="alert">
        Your KYC verification has already been submitted.
    </div>
    {/if}
</div>

{include file="mfooter.tpl"}