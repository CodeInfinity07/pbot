let userLoginData = {
  state: "loggedOut",
  ethAddress: "",
  buttonText: "Log in",
  publicName: "",
  JWT: "",
  config: { headers: { "Content-Type": "application/json" } }
}


if (typeof(backendPath) == 'undefined') {
  var backendPath = '';
}


// On accountsChanged
async function ethAccountsChanged() {      
  let accountsOnEnable = await web3.eth.getAccounts();
  let address = accountsOnEnable[0];
  address = address.toLowerCase();
  if (userLoginData.ethAddress != address) {
    userLogOut();
    getPublicName();
  }
  if (userLoginData.ethAddress != null && userLoginData.state == "needLogInToMetaMask") {
    userLoginData.state = "loggedOut";
  }
}

// Show current msg
function showMsg(id) {
  console.log(id);
}


// Show current address
function showAddress() {
  // document.getElementById('ethAddress').innerHTML = userLoginData.ethAddress;
}


// Show current button text
function showButtonText() {
  // document.getElementById('buttonText').innerHTML = userLoginData.buttonText;
}


async function userLoginOut() {
  clearProvider();
  
  if(userLoginData.state == "loggedOut" || userLoginData.state == "needMetamask") {
    await onConnectLoadWeb3Modal();
  }
  if (web3ModalProv) {
    try {
      userLogin();
    } catch (error) {
      console.log(error);
      userLoginData.state = 'needLogInToMetaMask';
      showMsg(userLoginData.state);
      return;
    }
  }
  else {
    userLoginData.state = 'needMetamask';
    return;
  }
}


async function userLogin() {
  if (userLoginData.state == "loggedIn") {
    userLoginData.state = "loggedOut";
    showMsg(userLoginData.state);
    userLoginData.JWT = "";
    userLoginData.buttonText = "Log in";
    return;
  }
  if (typeof window.web3 === "undefined") {
    userLoginData.state = "needMetamask";
    showMsg(userLoginData.state);
    return;
  }
  let accountsOnEnable = await ethereum.request({ method: 'eth_accounts' });
  let address = accountsOnEnable[0];
  address = address.toLowerCase();
  if (address == null) {
    userLoginData.state = "needLogInToMetaMask";
    showMsg(userLoginData.state);
    return;
  }
  // userLoginData.state = "signTheMessage";
  showMsg(userLoginData.state);

  axios.post(
    backendPath+"?get_nonce",
    {
      request: "login",
      address: address
    },
    userLoginData.config
  )
  .then(function(response) {
    if (response.data.substring(0, 5) != "Error") {
      let message = response.data;
      let publicAddress = address;
      handleSignMessage(message, publicAddress).then(handleAuthenticate);

      function handleSignMessage(message, publicAddress) {
        return new Promise((resolve, reject) =>  
          web3.eth.personal.sign(
            web3.utils.utf8ToHex(message),
            publicAddress,
            (err, signature) => {
              if (err) {
                userLoginData.state = "loggedOut";
                showMsg(userLoginData.state);
              }
              return resolve({ publicAddress, signature });
            }
          )
        );
      }

      function handleAuthenticate({ publicAddress, signature }) {
        axios
          .post(
            backendPath+"./login?metamask",
            {
              request: "auth",
              address: arguments[0].publicAddress,
              signature: arguments[0].signature
            },
            userLoginData.config
          )
          .then(function(response) {
            if (response.data[0] == "Success") {
              window.location.href = response.data[1];
            }
          })
          .catch(function(error) {
            console.error(error);
          });
      }
    } 
    else {
      console.log("Error: " + response.data);
    }
  })
  .catch(function(error) {
    console.error(error);
  });
} 
