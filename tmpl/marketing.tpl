{include file="mheader.tpl"}
<!-- ============================================================== -->
<!-- End Left Sidebar - style you can find in sidebar.scss  -->
<!-- ============================================================== -->
<!-- ============================================================== -->
<!-- Page wrapper  -->
<!-- ============================================================== -->
<div class="page-wrapper">

    <!-- ============================================================== -->
    <!-- Container fluid  -->
    <!-- ============================================================== -->
    <div class="container-fluid">
        <div class="row content">

            <div class="col-sm-12 text-left">
                <div class="card my-4">
                    <h5 class="card-header d-flex align-items-center bg-inverse bg-opacity-10 fw-400">{{$content.referral.title}}</h5>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-12">
                                <div class="form-group row">
                                    <label class="col-form-label col-md-2">{{$content.referral.content}}</label>
                                    <div class="col-md-10">
                                        <input class="form-control" type="datetime" name="datetime" value="{$settings.site_url}?ref={$userinfo.username}">
                                        <span class="form-text text-muted"><a href="referrals">{{$content.referrals.title}}</a> </span>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                    	<div class="card-arrow">
							<div class="card-arrow-top-left"></div>
							<div class="card-arrow-top-right"></div>
							<div class="card-arrow-bottom-left"></div>
							<div class="card-arrow-bottom-right"></div>
						</div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="card my-4">
                            	<div class="card-arrow">
							<div class="card-arrow-top-left"></div>
							<div class="card-arrow-top-right"></div>
							<div class="card-arrow-bottom-left"></div>
							<div class="card-arrow-bottom-right"></div>
						</div>
                            <h5 class="card-header d-flex align-items-center bg-inverse bg-opacity-10 fw-400">{{$content.marketing.title}}</h5>
                            <div class="card-body">
                                <p>{{$content.marketing.content}}{$settings.site_name}</p>
                                <!-- Nav tabs -->
                                <div class="vtabs">
                                   
                                    <!-- Tab panes -->
                                    <div class="tab-content">
                                        <div class="tab-pane active show" id="b1" role="tabpanel">

                                            <div class="row">
                                                <div class="col-md-12 col-xs-12 ">
                                                    <div class="card">
                                                        <div class="card-body">

                                                            <br><img src="images/125.gif"><br>125x125 Banner<br>
                                                            <div class="form-group">
                                                                <label>{{$content.banner.content}}</label>
                                                                <textarea class="form-control" rows="5" spellcheck="false">&lt;a href="{$settings.site_url}?ref={$userinfo.username}"&gt; &lt;img src="{$settings.site_url}images/125.gif" alt="" width="125" height="125" /&gt;</textarea>
                                                            </div>
                                                            <br><img src="images/468.gif"><br>468x60 Banner<br>
                                                            <div class="form-group">
                                                                <label>{{$content.banner.content}}</label>
                                                                <textarea class="form-control" rows="5" spellcheck="false">&lt;a href="{$settings.site_url}?ref={$userinfo.username}"&gt; &lt;img src="{$settings.site_url}images/125.gif" alt="" width="125" height="125" /&gt;</textarea>
                                                            </div>
                                                            <br><img src="images/728.gif"><br>728x90 Banner<br>
                                                            <div class="form-group">
                                                                <label>{{$content.banner.content}}</label>
                                                                <textarea class="form-control" rows="5" spellcheck="false">&lt;a href="{$settings.site_url}?ref={$userinfo.username}"&gt; &lt;img src="{$settings.site_url}images/125.gif" alt="" width="125" height="125" /&gt;</textarea>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                            </div>

                                        </div>
                                

                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    {include file="mfooter.tpl"}