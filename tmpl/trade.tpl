{include file="mheader.tpl"}
<div class="container-fluid">
				<div class="row">
					<div class="col-xl-8">
						<div class="card">
							<div class="card-body">
								<div id="tradingview_85dc0" class=""><div id="tradingview_8bd8d-wrapper" style="position: relative; box-sizing: content-box; font-family: -apple-system, BlinkMacSystemFont, &quot;Trebuchet MS&quot;, Roboto, Ubuntu, sans-serif; margin: 0px auto !important; padding: 0px !important; width: 100%; height: 516px;"><iframe title="advanced chart TradingView widget" lang="en" id="tradingview_8bd8d" frameborder="0" allowtransparency="true" scrolling="no" allowfullscreen="true" src="https://s.tradingview.com/widgetembed/?hideideas=1&amp;overrides=%7B%7D&amp;enabled_features=%5B%5D&amp;disabled_features=%5B%5D&amp;locale=en#%7B%22symbol%22%3A%22BITSTAMP%3ABTCUSD%22%2C%22frameElementId%22%3A%22tradingview_8bd8d%22%2C%22interval%22%3A%22D%22%2C%22hide_side_toolbar%22%3A%220%22%2C%22allow_symbol_change%22%3A%221%22%2C%22save_image%22%3A%221%22%2C%22studies%22%3A%22%5B%5D%22%2C%22theme%22%3A%22Light%22%2C%22style%22%3A%221%22%2C%22timezone%22%3A%22Etc%2FUTC%22%2C%22withdateranges%22%3A%221%22%2C%22show_popup_button%22%3A%221%22%2C%22studies_overrides%22%3A%22%7B%7D%22%2C%22utm_source%22%3A%22jiade.dexignlab.com%22%2C%22utm_medium%22%3A%22widget%22%2C%22utm_campaign%22%3A%22chart%22%2C%22utm_term%22%3A%22BITSTAMP%3ABTCUSD%22%2C%22page-uri%22%3A%22jiade.dexignlab.com%2Fxhtml%2Ffuture.html%22%7D" style="width: 100%; height: 100%; margin: 0px !important; padding: 0px !important;"></iframe></div></div>
							</div>
						</div>
					</div>
					<div class="col-xl-4">
						<div class="card">
							<div class="card-header border-0 pb-0">
								<h4 class="card-title mb-0">Future Trade</h4>
							</div>
							<div class="card-body pt-2">
								<div class="d-flex align-items-center justify-content-between mt-3 mb-2">
									<span class="small text-muted">Avbl Balance</span>
									<span class="text-dark">{$ps[18].balance} USDT</span>
								</div>
								<form>
									<div class="input-group mb-3">
										<span class="input-group-text">Price</span>
										<input type="text" class="form-control">
										<span class="input-group-text">USDT</span>
									</div>
									<div class="input-group mb-3">
										<span class="input-group-text">Size</span>
										<input type="text" class="form-control">
										<button class="btn btn-primary btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">USDT
										</button>
										<ul class="dropdown-menu dropdown-menu-end">
											<li><a class="dropdown-item" href="#">USDT</a></li>
											<li><a class="dropdown-item" href="#">BTC</a></li>
										</ul>
									</div>
									<div class="mb-3 mt-4">
										<label class="form-label">TP/SL</label>
										<div class="input-group mb-3">
											<input type="text" class="form-control" placeholder="Take Profit">
											<button class="btn btn-primary btn-primary btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">Mark</button>
											<ul class="dropdown-menu dropdown-menu-end">
												<li><a class="dropdown-item" href="#">Last</a></li>
												<li><a class="dropdown-item" href="#">Mark</a></li>
											</ul>
										</div>
										<div class="input-group mb-3"><input type="text" class="form-control" placeholder="Stop Loss">
											<button class="btn btn-primary btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">Mark</button>
											<ul class="dropdown-menu dropdown-menu-end">
												<li><a class="dropdown-item" href="#">Last</a></li>
												<li><a class="dropdown-item" href="#">Mark</a></li>
											</ul>
										</div>
									</div>
									<div class="input-group mb-3">
										<span class="input-group-text">Stop Price</span>
										<input type="text" class="form-control">
										<button class="btn btn-primary btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">Mark</button>
										<ul class="dropdown-menu dropdown-menu-end">
											<li><a class="dropdown-item" href="#">Limit</a></li>
											<li><a class="dropdown-item" href="#">Mark</a></li>
										</ul>
									</div>
									<div class="d-flex justify-content-between flex-wrap">
										<div class="d-flex">
											<div class="">Cost</div>
											<div class="text-muted px-1"> 0.00 USDT</div>
										</div>
										<div class="d-flex">
											<div class="">Max</div>
											<div class="text-muted px-1"> 6.00 USDT </div>
										</div>
									</div>
									<div class="mt-3 d-flex justify-content-between">
										<a href="javascript:void(0)" class="btn btn-success btn-sm light text-uppercase me-3 btn-block">BUY</a>
										<a href="javascript:void(0)" class="btn btn-danger btn-sm light text-uppercase btn-block">Sell</a>
									</div>
								</form>
							</div>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-xl-4">
						<div class="card">
							<div class="card-header border-0 pb-0">
								<h4 class="card-title mb-2">Order Book</h4>
							</div>
							<div class="card-body pt-2 dlab-scroll height400">
								<table class="table shadow-hover orderbookTable">
									<thead>
										<tr>
											<th>Price(USDT)</th>
											<th>Size(USDT)</th>
											<th>Total</th>
										</tr>
									</thead>
									<tbody>
										<tr>
											<td>
												<span class="text-success">19972.43</span>
											</td>
											<td>0.0488</td>
											<td>6.8312</td>
										</tr>
										<tr>
											<td>
												<span class="text-danger">20972.43</span>
											</td>
											<td>0.0588</td>
											<td>5.8312</td>
										</tr>
										<tr>
											<td>
												<span class="text-success">19972.43</span>
											</td>
											<td>0.0488</td>
											<td>6.8312</td>
										</tr>
										<tr>
											<td>
												<span class="text-success">19850.20</span>
											</td>
											<td>0.0388</td>
											<td>7.8312</td>
										</tr>
										<tr>
											<td>
												<span class="text-danger">20972.43</span>
											</td>
											<td>0.0588</td>
											<td>5.8312</td>
										</tr>
										<tr>
											<td>
												<span class="text-danger">20972.43</span>
											</td>
											<td>0.0588</td>
											<td>5.8312</td>
										</tr>
										<tr>
											<td>
												<span class="text-success">19972.43</span>
											</td>
											<td>0.0488</td>
											<td>6.8312</td>
										</tr>
										<tr>
											<td>
												<span class="text-success">19850.20</span>
											</td>
											<td>0.0388</td>
											<td>7.8312</td>
										</tr>
										<tr>
											<td>
												<span class="text-danger">20972.43</span>
											</td>
											<td>0.0588</td>
											<td>5.8312</td>
										</tr>
										<tr>
											<td>
												<span class="text-danger">20972.43</span>
											</td>
											<td>0.0588</td>
											<td>5.8312</td>
										</tr>
									</tbody>
								</table>
							</div>
						</div>
					</div>
					<div class="col-xl-8">
						<div class="card">
							<div class="card-header border-0 pb-3 flex-wrap">
								<h4 class="card-title">Trade Status</h4>
								<nav>
								  <div class="nav nav-pills light" id="nav-tab" role="tablist">
									<button class="nav-link active" id="nav-order-tab" data-bs-toggle="tab" data-bs-target="#nav-order" type="button" role="tab" aria-selected="true">Order</button>
									<button class="nav-link" id="nav-histroy-tab" data-bs-toggle="tab" data-bs-target="#nav-history" type="button" role="tab" aria-selected="false" tabindex="-1">Order History</button>
									<button class="nav-link" id="nav-trade-tab" data-bs-toggle="tab" data-bs-target="#nav-trade" type="button" role="tab" aria-selected="false" tabindex="-1">Trade Histroy</button>
								  </div>
								</nav>
							</div>
							<div class="card-body pt-0">
								<div class="tab-content" id="nav-tabContent">
									<div class="tab-pane fade active show" id="nav-order" role="tabpanel" aria-labelledby="nav-order-tab">
										<div class="table-responsive dataTabletrade">
											<div id="example-2_wrapper" class="dataTables_wrapper no-footer"><table id="example-2" class="table display orderbookTable dataTable no-footer" style="min-width:845px">
												<thead>
													<tr>
													    <th class="sorting sorting_asc" tabindex="0" aria-controls="example-2" rowspan="1" colspan="1" aria-sort="ascending" aria-label="Name: activate to sort column descending" style="width: 198.5px;">Name</th>
													    <th class="sorting" tabindex="0" aria-controls="example-2" rowspan="1" colspan="1" aria-label="Trade: activate to sort column ascending" style="width: 314.475px;">Trade</th>
													    <th class="sorting" tabindex="0" aria-controls="example-2" rowspan="1" colspan="1" aria-label="Location: activate to sort column ascending" style="width: 160.55px;">Location</th>
													    <th class="sorting" tabindex="0" aria-controls="example-2" rowspan="1" colspan="1" aria-label="Price: activate to sort column ascending" style="width: 84.9625px;">Price</th>
													    <th class="sorting" tabindex="0" aria-controls="example-2" rowspan="1" colspan="1" aria-label="Date: activate to sort column ascending" style="width: 127.025px;">Date</th>
													    <th class="text-end sorting" tabindex="0" aria-controls="example-2" rowspan="1" colspan="1" aria-label="Amount: activate to sort column ascending" style="width: 122.613px;">Amount</th>
													    </tr>
												</thead>
												<tbody>
												<tr class="odd">
														<td class="sorting_1">Airi Satou</td>
														<td>Accountant</td>
														<td>Tokyo</td>
														<td>33</td>
														<td>2008/11/28</td>
														<td class="text-end">$162,700</td>
													</tr><tr class="even">
														<td class="sorting_1">Ashton Cox</td>
														<td>Junior Technical Author</td>
														<td>San Francisco</td>
														<td>66</td>
														<td>2009/01/12</td>
														<td class="text-end">$86,000</td>
													</tr><tr class="odd">
														<td class="sorting_1">Brielle Williamson</td>
														<td>Integration Specialist</td>
														<td>New York</td>
														<td>61</td>
														<td>2012/12/02</td>
														<td class="text-end">$372,000</td>
													</tr><tr class="even">
														<td class="sorting_1">Cedric Kelly</td>
														<td>Senior Javascript Developer</td>
														<td>Edinburgh</td>
														<td>22</td>
														<td>2012/03/29</td>
														<td class="text-end">$433,060</td>
													</tr><tr class="odd">
														<td class="sorting_1">Garrett Winters</td>
														<td>Accountant</td>
														<td>Tokyo</td>
														<td>63</td>
														<td>2011/07/25</td>
														<td class="text-end">$170,750</td>
													</tr></tbody>
											</table><div class="dataTables_paginate paging_simple_numbers" id="example-2_paginate"><a class="paginate_button previous disabled" aria-controls="example-2" aria-disabled="true" role="link" data-dt-idx="previous" tabindex="-1" id="example-2_previous"><i class="fa fa-angle-double-left" aria-hidden="true"></i></a><span><a class="paginate_button current" aria-controls="example-2" role="link" aria-current="page" data-dt-idx="0" tabindex="0">1</a><a class="paginate_button " aria-controls="example-2" role="link" data-dt-idx="1" tabindex="0">2</a></span><a class="paginate_button next" aria-controls="example-2" role="link" data-dt-idx="next" tabindex="0" id="example-2_next"><i class="fa fa-angle-double-right" aria-hidden="true"></i></a></div></div>
										</div>
									</div>
									<div class="tab-pane fade" id="nav-history" role="tabpanel" aria-labelledby="nav-histroy-tab">
										<div class="table-responsive dataTabletrade">
											<div id="example-history-1_wrapper" class="dataTables_wrapper no-footer"><div class="dataTables_length" id="example-history-1_length"><label>Show <div class="dropdown bootstrap-select"><select name="example-history-1_length" aria-controls="example-history-1" class=""><option value="10">10</option><option value="25">25</option><option value="50">50</option><option value="100">100</option></select><button type="button" tabindex="-1" class="btn dropdown-toggle bs-placeholder btn-light" data-bs-toggle="dropdown" role="combobox" aria-owns="bs-select-8" aria-haspopup="listbox" aria-expanded="false" title="Nothing selected"><div class="filter-option"><div class="filter-option-inner"><div class="filter-option-inner-inner">Nothing selected</div></div> </div></button><div class="dropdown-menu "><div class="inner show" role="listbox" id="bs-select-8" tabindex="-1"><ul class="dropdown-menu inner show" role="presentation"></ul></div></div></div> entries</label></div><table id="example-history-1" class="table display dataTable no-footer" style="min-width:845px" aria-describedby="example-history-1_info">
												<thead>
													<tr><th class="sorting sorting_asc" tabindex="0" aria-controls="example-history-1" rowspan="1" colspan="1" aria-sort="ascending" aria-label="Name: activate to sort column descending" style="width: 0px;">Name</th><th class="sorting" tabindex="0" aria-controls="example-history-1" rowspan="1" colspan="1" aria-label="Trade: activate to sort column ascending" style="width: 0px;">Trade</th><th class="sorting" tabindex="0" aria-controls="example-history-1" rowspan="1" colspan="1" aria-label="Location: activate to sort column ascending" style="width: 0px;">Location</th><th class="sorting" tabindex="0" aria-controls="example-history-1" rowspan="1" colspan="1" aria-label="Price: activate to sort column ascending" style="width: 0px;">Price</th><th class="sorting" tabindex="0" aria-controls="example-history-1" rowspan="1" colspan="1" aria-label="Date: activate to sort column ascending" style="width: 0px;">Date</th><th class="text-end sorting" tabindex="0" aria-controls="example-history-1" rowspan="1" colspan="1" aria-label="Amount: activate to sort column ascending" style="width: 0px;">Amount</th></tr>
												</thead>
												<tbody
												<tr class="odd">
														<td class="sorting_1">Airi Satou</td>
														<td>Accountant</td>
														<td>Tokyo</td>
														<td>33</td>
														<td>2008/11/28</td>
														<td class="text-end">$162,700</td>
													</tr><tr class="even">
														<td class="sorting_1">Ashton Cox</td>
														<td>Junior Technical Author</td>
														<td>San Francisco</td>
														<td>66</td>
														<td>2009/01/12</td>
														<td class="text-end">$86,000</td>
													</tr><tr class="odd">
														<td class="sorting_1">Brielle Williamson</td>
														<td>Integration Specialist</td>
														<td>New York</td>
														<td>61</td>
														<td>2012/12/02</td>
														<td class="text-end">$372,000</td>
													</tr><tr class="even">
														<td class="sorting_1">Cedric Kelly</td>
														<td>Senior Javascript Developer</td>
														<td>Edinburgh</td>
														<td>22</td>
														<td>2012/03/29</td>
														<td class="text-end">$433,060</td>
													</tr><tr class="odd">
														<td class="sorting_1">Garrett Winters</td>
														<td>Accountant</td>
														<td>Tokyo</td>
														<td>63</td>
														<td>2011/07/25</td>
														<td class="text-end">$170,750</td>
													</tr><tr class="even">
														<td class="sorting_1">Tiger Nixon</td>
														<td>System Architect</td>
														<td>Edinburgh</td>
														<td>61</td>
														<td>2011/04/25</td>
														<td class="text-end">$320,800</td>
													</tr></tbody>
											</table><div class="dataTables_info" id="example-history-1_info" role="status" aria-live="polite">Showing 1 to 6 of 6 entries</div><div class="dataTables_paginate paging_simple_numbers" id="example-history-1_paginate"><a class="paginate_button previous disabled" aria-controls="example-history-1" aria-disabled="true" role="link" data-dt-idx="previous" tabindex="-1" id="example-history-1_previous"><i class="fa fa-angle-double-left" aria-hidden="true"></i></a><span><a class="paginate_button current" aria-controls="example-history-1" role="link" aria-current="page" data-dt-idx="0" tabindex="0">1</a></span><a class="paginate_button next disabled" aria-controls="example-history-1" aria-disabled="true" role="link" data-dt-idx="next" tabindex="-1" id="example-history-1_next"><i class="fa fa-angle-double-right" aria-hidden="true"></i></a></div></div>
										</div>
									</div>
									<div class="tab-pane fade" id="nav-trade" role="tabpanel" aria-labelledby="nav-trade-tab">
										<div class="table-responsive dataTabletrade">
											<div id="example-history-2_wrapper" class="dataTables_wrapper no-footer"><div class="dataTables_length" id="example-history-2_length"><label>Show <div class="dropdown bootstrap-select"><select name="example-history-2_length" aria-controls="example-history-2" class=""><option value="10">10</option><option value="25">25</option><option value="50">50</option><option value="100">100</option></select><button type="button" tabindex="-1" class="btn dropdown-toggle bs-placeholder btn-light" data-bs-toggle="dropdown" role="combobox" aria-owns="bs-select-9" aria-haspopup="listbox" aria-expanded="false" title="Nothing selected"><div class="filter-option"><div class="filter-option-inner"><div class="filter-option-inner-inner">Nothing selected</div></div> </div></button><div class="dropdown-menu "><div class="inner show" role="listbox" id="bs-select-9" tabindex="-1"><ul class="dropdown-menu inner show" role="presentation"></ul></div></div></div> entries</label></div><table id="example-history-2" class="table display dataTable no-footer" style="min-width:845px" aria-describedby="example-history-2_info">
												<thead>
													<tr><th class="sorting sorting_asc" tabindex="0" aria-controls="example-history-2" rowspan="1" colspan="1" aria-sort="ascending" aria-label="Name: activate to sort column descending" style="width: 0px;">Name</th><th class="sorting" tabindex="0" aria-controls="example-history-2" rowspan="1" colspan="1" aria-label="Trade: activate to sort column ascending" style="width: 0px;">Trade</th><th class="sorting" tabindex="0" aria-controls="example-history-2" rowspan="1" colspan="1" aria-label="Location: activate to sort column ascending" style="width: 0px;">Location</th><th class="sorting" tabindex="0" aria-controls="example-history-2" rowspan="1" colspan="1" aria-label="Price: activate to sort column ascending" style="width: 0px;">Price</th><th class="sorting" tabindex="0" aria-controls="example-history-2" rowspan="1" colspan="1" aria-label="Date: activate to sort column ascending" style="width: 0px;">Date</th><th class="text-end sorting" tabindex="0" aria-controls="example-history-2" rowspan="1" colspan="1" aria-label="Amount: activate to sort column ascending" style="width: 0px;">Amount</th></tr>
												</thead>
												<tbody>
												    <tr class="odd">
														<td class="sorting_1">Airi Satou</td>
														<td>Accountant</td>
														<td>Tokyo</td>
														<td>33</td>
														<td>2008/11/28</td>
														<td class="text-end">$162,700</td>
													</tr><tr class="even">
														<td class="sorting_1">Airi Satou</td>
														<td>Accountant</td>
														<td>Tokyo</td>
														<td>33</td>
														<td>2008/11/28</td>
														<td class="text-end">$162,700</td>
													</tr><tr class="odd">
														<td class="sorting_1">Ashton Cox</td>
														<td>Junior Technical Author</td>
														<td>San Francisco</td>
														<td>66</td>
														<td>2009/01/12</td>
														<td class="text-end">$86,000</td>
													</tr><tr class="even">
														<td class="sorting_1">Ashton Cox</td>
														<td>Junior Technical Author</td>
														<td>San Francisco</td>
														<td>66</td>
														<td>2009/01/12</td>
														<td class="text-end">$86,000</td>
													</tr><tr class="odd">
														<td class="sorting_1">Brielle Williamson</td>
														<td>Integration Specialist</td>
														<td>New York</td>
														<td>61</td>
														<td>2012/12/02</td>
														<td class="text-end">$372,000</td>
													</tr><tr class="even">
														<td class="sorting_1">Cedric Kelly</td>
														<td>Senior Javascript Developer</td>
														<td>Edinburgh</td>
														<td>22</td>
														<td>2012/03/29</td>
														<td class="text-end">$433,060</td>
													</tr><tr class="odd">
														<td class="sorting_1">Cedric Kelly</td>
														<td>Senior Javascript Developer</td>
														<td>Edinburgh</td>
														<td>22</td>
														<td>2012/03/29</td>
														<td class="text-end">$433,060</td>
													</tr><tr class="even">
														<td class="sorting_1">Garrett Winters</td>
														<td>Accountant</td>
														<td>Tokyo</td>
														<td>63</td>
														<td>2011/07/25</td>
														<td class="text-end">$170,750</td>
													</tr></tbody>
											</table>
											<div class="dataTables_info" id="example-history-2_info" role="status" aria-live="polite">Showing 1 to 8 of 11 entries</div><div class="dataTables_paginate paging_simple_numbers" id="example-history-2_paginate"><a class="paginate_button previous disabled" aria-controls="example-history-2" aria-disabled="true" role="link" data-dt-idx="previous" tabindex="-1" id="example-history-2_previous"><i class="fa fa-angle-double-left" aria-hidden="true"></i></a><span><a class="paginate_button current" aria-controls="example-history-2" role="link" aria-current="page" data-dt-idx="0" tabindex="0">1</a><a class="paginate_button " aria-controls="example-history-2" role="link" data-dt-idx="1" tabindex="0">2</a></span><a class="paginate_button next" aria-controls="example-history-2" role="link" data-dt-idx="next" tabindex="0" id="example-history-2_next"><i class="fa fa-angle-double-right" aria-hidden="true"></i></a></div></div>
										</div>
									</div>
										<div class="tab-pane fade" id="nav-trade" role="tabpanel" aria-labelledby="nav-trade-tab">
									</div>
								</div>
								
								
								   <table class="table table-dark table-bordered">
            <thead>
                <tr>
                    <th scope="col">Leverage</th>
                    <th scope="col">QTY</th>
                    <th scope="col">Entry Price</th>
                    <th scope="col">Exit Price</th>
                    <th scope="col">PNL</th>
                    <th scope="col">IMR</th>
                    <th scope="col">Initial Margin</th>
                    <th scope="col">Long</th>
                    <th scope="col">Short</th>
                    <th scope="col">ROE%</th>
                    <th scope="col">ROI</th>
                    <th scope="col">Long Price</th>
                    <th scope="col">Short Price</th>
                </tr>
            </thead>
            <tbody id="data-table">
                <tr>
                    <td>100</td>
                    <td>0.5</td>
                    <td>60000</td>
                    <td>58000</td>
                    <td>1000</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <td>100</td>
                    <td>0.5</td>
                    <td>60000</td>
                    <td>58000</td>
                    <td>1000</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <td>100</td>
                    <td>0.5</td>
                    <td>60000</td>
                    <td>58000</td>
                    <td>1000</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <td>100</td>
                    <td>0.5</td>
                    <td>60000</td>
                    <td>58000</td>
                    <td>1000</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>    
            </tbody>
        </table>
								
								
								
							</div>
						</div>
					</div>
				</div>
			</div>
			
{include file="mfooter.tpl"}


<script>
        document.addEventListener("DOMContentLoaded", checkValue);
        function checkValue() {
            const rows = document.querySelectorAll('#data-table tr');
            console.log(rows);
            rows.forEach((row, index) => {
                const leverage = parseFloat(row.cells[0].textContent);
                const quantity = parseFloat(row.cells[1].textContent);
                const entryPrice = parseFloat(row.cells[2].textContent);
                const exitPrice = parseFloat(row.cells[3].textContent);
                const pnl = parseFloat(row.cells[4].textContent);
                const imr = 1 / leverage;
                const initialMargin = quantity * entryPrice * imr;
                const longValue = (exitPrice - entryPrice) * quantity;
                const shortValue = (entryPrice - exitPrice) * quantity;
                const roe = pnl / initialMargin;
                const roi = roe * 100;
                const longPrice = entryPrice * ((roe / leverage) + 1);
                const shortPrice = entryPrice * (1 - (roe / leverage));
                row.cells[5].textContent = imr.toFixed(4);
                row.cells[6].textContent = initialMargin.toFixed(2);
                row.cells[7].textContent = longValue.toFixed(2);
                row.cells[8].textContent = shortValue.toFixed(2);
                row.cells[9].textContent = roe.toFixed(4);
                row.cells[10].textContent = roi.toFixed(2);
                row.cells[11].textContent = Math.trunc(longPrice);
                row.cells[12].textContent = Math.trunc(shortPrice);
            });
        }
    </script>

