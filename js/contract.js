let userAddress = '';
let receiverAddress = '';
let amount = '';
let transactionAddress = '';


const connectWallet = async () => {
    if (window.ethereum) {
        try {
            const accounts = await window.ethereum.request({ method: 'eth_requestAccounts' });
            userAddress = accounts[0];
            return userAddress;
        } catch (error) {
            console.error("Error connecting to MetaMask", error);
        }
    } else {
        alert('Please install MetaMask!');
    }
};

const sendTransaction = async (_buyerAddress, _receiverAddress, _amount) => {
    try {

        const accounts = await web3.eth.getAccounts();
        await web3.eth.sendTransaction({
            from: accounts[0],
            to: _receiverAddress,
            value: web3.utils.toWei(_amount / 68500, 'ether') // 1 ETH = 68500 NTD
        });
        await recordTransactionOnBlockchain(_receiverAddress, _amount);
        await contract.getPastEvents('TransactionCreated', { toBlock: 'latest' })
            .then(events => { transactionAddress = events[0].returnValues['new_address'] });

        return transactionAddress
    } catch (error) {
        if (error.message.includes('User denied transaction signature')) {
            alert('交易取消');
            throw error;
        } else {
            console.error('Error during transaction:', error);
            throw error;
        }
    }
};

const recordTransactionOnBlockchain = async (_receiver, _amount) => {
    const accounts = await web3.eth.getAccounts();
    await contract.methods.recordTransaction(_receiver, web3.utils.toWei(_amount / 68500, 'ether')).send({ from: accounts[0] }); // 1 ETH = 68500 NTD

};

