const web3 = new Web3(window.ethereum);
const contractABI = [];
const contractAddress = '';

// export const contract = new web3.eth.Contract(contractABI, contractAddress);
// export const ABI = contractABI;
// export default web3;
const contract = new web3.eth.Contract(contractABI, contractAddress);
const ABI = contractABI;

