{include file="mheader.tpl"}
   <style>
  
        .game-container {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            max-width: 500px;
            width: 100%;
        }
        #coin {
            width: 150px;
            height: 150px;
            margin: 20px auto;
            transition: transform 1s ease-in;
            transform-style: preserve-3d;
        }
        #coin div {
            width: 100%;
            height: 100%;
            position: absolute;
            backface-visibility: hidden;
        }
        .heads {
            background: url('images/head.png') no-repeat center center;
            background-size: contain;
        }
        .tails {
            background: url('images/tail.png') no-repeat center center;
            background-size: contain;
            transform: rotateY(180deg);
        }
        #coin.animate-flip {
            animation: flip 2s linear infinite;
        }
        @keyframes flip {
            0% { transform: rotateY(0); }
            100% { transform: rotateY(1800deg); }
        }
        .cc-selector input {
            margin: 0;
            padding: 0;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
        }
        .cc-selector input:active + img {
            opacity: 0.9;
        }
        .cc-selector input:checked + img {
            -webkit-filter: none;
            -moz-filter: none;
            filter: none;
            border: 2px solid #007bff;
        }
        .cc-selector img {
            cursor: pointer;
            background-size: contain;
            background-repeat: no-repeat;
            display: inline-block;
            width: 70px;
            height: 70px;
            -webkit-transition: all 100ms ease-in;
            -moz-transition: all 100ms ease-in;
            transition: all 100ms ease-in;
            -webkit-filter: brightness(1.8) grayscale(1) opacity(0.7);
            -moz-filter: brightness(1.8) grayscale(1) opacity(0.7);
            filter: brightness(1.8) grayscale(1) opacity(0.7);
            border-radius: 50%;
            margin: 0 10px;
        }
        .cc-selector img:hover {
            -webkit-filter: brightness(1.2) grayscale(0.5) opacity(0.9);
            -moz-filter: brightness(1.2) grayscale(0.5) opacity(0.9);
            filter: brightness(1.2) grayscale(0.5) opacity(0.9);
        }
    </style>
        {if $alert}
<div class="{$alert_class}">
    <span>{$alert_message}</span>
</div>
{/if}
<div class="game-container">
        <div id="coin" >
            <div class="heads"></div>
            <div class="tails"></div>
        </div>
        <form id="game" method="post">
            <h3 class="f-size--28 mb-4 text-center">Current Balance : <span class="base--color">
                <span class="bal">0.00</span> USD</span>
            </h3>
            <div class="form-group">
                <div class="input-group mb-3">
                    <input class="form-control amount-field" name="amount" type="text" value="" placeholder="Enter amount" autocomplete="off" id="invest">
                    <span class="input-group-text" id="basic-addon2">USD</span>
                </div>
                <small class="form-text text-muted"><i class="fas fa-info-circle mr-2"></i>Minimum : $1.00 USD | Maximum  : $100.00 USD |  <span class="text--warning">Win Amount  % </span></small>
            </div>
            <div class="form-group justify-content-center d-flex mt-5">
                <div class="cc-selector">
                    <label for="head">
                        <input id="head" type="radio" name="game" value="1" />
                        <img src="images/head.png" alt="Heads">
                    </label>
                    <label for="tail">
                        <input id="tail" type="radio" name="game" value="0" />
                        <img src="images/tail.png" alt="Tails">
                    </label>
                </div>
            </div>
            <div class="mt-5 text-center">
                <button class="btn btn-primary w-100 game text-center" id="flip" type="submit">Play Now</button>
                <a class="game-instruction mt-2 d-block" data-bs-toggle="modal" data-bs-target="#exampleModalCenter">Game Instruction <i class="fas fa-info-circle"></i></a>
            </div>
        </form>
        <div id="result" class="mt-3 text-center"></div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

{literal}
    <script>
        $(document).ready(function() {
            const coin = $('#coin');
            const form = $('#game');
            const result = $('#result');
            
            coin.addClass('animate-flip');

            form.on('submit', function(e) {
                e.preventDefault();
                
                const amount = $('#invest').val();
                const choice = $('input[name="game"]:checked').val();

                if (!amount || !choice) {
                    alert('Please enter an amount and select heads or tails.');
                    return;
                }

                coin.show().removeClass('animate-flip');
                $('#flip').prop('disabled', true);

                // Simulate coin flip
                setTimeout(() => {
                    const gameResult = Math.random() < 0.5 ? '1' : '0'; // '1' for heads, '0' for tails
                    coin.css('transform', `rotateY(${gameResult === '1' ? 0 : 180}deg)`);

                    // Send form data to server
                    $.post('head_tail', form.serialize(), function(response) {
                        // Handle server response here
                        setTimeout(() => {
                            const win = gameResult === choice;
                            result.text(win ? 'You won!' : 'You lost!').removeClass().addClass(win ? 'text-success' : 'text-danger');
                            $('#flip').prop('disabled', false);
                            if (response.newBalance !== undefined) {
                                updateBalance(response.newBalance);
                            }
                        }, 1000);
                    }).fail(function() {
                        alert('There was an error processing your game. Please try again.');
                        $('#flip').prop('disabled', false);
                    });
                }, 2000);
            });

            function updateBalance(newBalance) {
                $('.bal').text(parseFloat(newBalance).toFixed(2));
            }
        });
    </script>
    {/literal}
{include file="mfooter.tpl"}




  
        {if $alert}
<div class="{$alert_class}">
    <span>{$alert_message}</span>
</div>
{/if}
{literal}
<style>
[type=radio] { 
  position: absolute;
  opacity: 0;
  width: 0;
  height: 0;
}

/* IMAGE STYLES */
[type=radio] + img {
  cursor: pointer;
}

/* CHECKED STYLES */
[type=radio]:checked + img {
  outline: 2px solid #f00;
}
</style>
{/literal}
       <section class="pt-120 pb-120">
        <div class="container">
            <div class="row">
                <div class="col-lg-6">
                    <div class="card-body h-100 middle-el">
                        <div class="alt"></div>
                        <div class="game-details-left">
                            <div class="game-details-left__body">
                                <div class="flp">
                                    <div id="coin-flip-cont">
                                        <div class="flipcoin animate" id="coin">
                                            <div class="flpng coins-wrapper">
                                                <div class="front"><img src="images/head.png" alt=""></div>
                                                <div class="back"><img src="images/tail.png" alt=""></div>
                                            </div>
                                            <div class="headCoin d-none">
                                                <div class="front"><img src="images/head.png" alt=""></div>
                                                <div class="back"><img src="images/tail.png" alt=""></div>
                                            </div>
                                            <div class="tailCoin d-none">
                                                <div class="front"><img src="images/tail.png" alt=""></div>
                                                <div class="back"><img src="images/head.png" alt=""></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="cd-ft"></div>
                    </div>
                </div>
                <div class="col-lg-6 mt-lg-0 mt-5">
                    <div class="game-details-right">
                        <form id="game" method="post">
                          <h3 class="f-size--28 mb-4 text-center">Current Balance : <span class="base--color">
                                    <span class="bal">0.00</span> USD</span>
                            </h3>
                            <div class="form-group">
                                <div class="input-group mb-3">
                                    <input class="form-control amount-field" name="amount" type="text" value="" placeholder="Enter amount" autocomplete="off" id="invest">
                                    <span class="input-group-text" id="basic-addon2">USD</span>
                                </div>
                                <small class="form-text text-muted"><i class="fas fa-info-circle mr-2"></i>Minimum : $1.00 USD | Maximum  : $100.00 USD |  <span class="text--warning">Win Amount  % </span> </small>
                            </div>
                            <div class="form-group justify-content-center d-flex mt-5">
                                   <div class="cc-selector">
                                       <label  for="head">
                                        <input  id="head" type="radio" name="game" value="1" />
                                        <img src="images/head.png" alt="">
                                        </label>
                                        <label for="tail">
                                        <input id="tail" type="radio" name="game" value="0" />
                                         <img src="images/tail.png" alt="">
                                        </label>
                                    </div>
                               
                            </div>
                            <div class="mt-5 text-center">
                                <button class="cmn-btn w-100 game text-center" id="flip" type="submit" >Play Now</button>
                                <a class="game-instruction mt-2" data-bs-toggle="modal" data-bs-target="#exampleModalCenter">Game Instruction <i class="las la-info-circle"></i></a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
                    

{include file="mfooter.tpl"}



