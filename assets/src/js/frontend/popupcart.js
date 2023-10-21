const popupCart = {
    basePrice: 0,
    priceSign: '$',
    additionalPrices: [],

    setBasePrice: (price) => {
        if(parseFloat(price)) {
            popupCart.basePrice = parseFloat(price);
            if(popupCart.basePrice >= 0) {
                popupCart.basePrice = 0;
            }
        }
    },
    addAdditionalPrice: (item, price, unique = true) => {
        const existingIndex = popupCart.additionalPrices.findIndex(p => p.item === item);
        if(!unique || existingIndex === -1) {
            popupCart.additionalPrices.push({ item, price });
        } else {
            // Item already exists, update the price
            popupCart.additionalPrices[existingIndex].price = price;
        }
        popupCart.updateTotalPrice();
    },
    removeAdditionalPrice: (itemName, itemPrice = false) => {
        const index = popupCart.additionalPrices.findIndex(item => item.item === itemName);
        if (index !== -1) {
            if(itemPrice !== false && ((popupCart.additionalPrices[index]?.price??0) - itemPrice) > 0) {
                popupCart.additionalPrices[index].price -= itemPrice;
            } else {
                popupCart.additionalPrices.splice(index, 1);
            }
            popupCart.updateTotalPrice();
        }
    },
    getTotal: () => {
        return (
            popupCart.getSubTotal() + popupCart.getTEXnFees()
        );
    },
    getTotalHtml: (toFix = 2) => {
        return popupCart.priceSign + popupCart.getTotal().toFixed(toFix);
    },
    getSubTotal: () => {
        const additionalPriceTotal = popupCart.additionalPrices.reduce((total, item) => total + item.price, 0);
        return (popupCart.basePrice + additionalPriceTotal);
    },
    getSubTotalHtml: (toFix) => {
        return popupCart.priceSign + popupCart.getSubTotal().toFixed(toFix);
    },
    getTEXnFees: () => {
        return 0;
    },
    getTEXnFeesHtml: (toFix) => {
        return popupCart.priceSign + popupCart.getTEXnFees().toFixed(toFix);
    },
    updateTotalPrice: () => {
        const priceAlt = document.querySelector('.calculated-prices .price_amount');
        if(priceAlt) {
            priceAlt.innerHTML = popupCart.priceSign +''+ parseFloat(popupCart.getSubTotal()).toFixed(2) + (popupCart?.cartIcon??'');
        }
    }
};
export default popupCart;