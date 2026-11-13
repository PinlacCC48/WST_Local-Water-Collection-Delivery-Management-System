
document.addEventListener("DOMContentLoaded", () => {
  // Theme Toggle
  const themeToggleBtn = document.getElementById("themeToggleBtn");
  const themeToggleImg = document.getElementById("themeToggleImg");

  const applyTheme = (isDark) => {
    document.body.classList.toggle("dark-mode", isDark);
    if (themeToggleImg) {
      themeToggleImg.src = isDark ? "Darkmode_Pic.png" : "Lightmode_Pic.png";
      themeToggleImg.alt = isDark ? "Dark Mode Active" : "Light Mode Active";
    }
  };

  try {
    applyTheme(localStorage.getItem("majestic_theme") === "dark");
  } catch (err) {
    applyTheme(false);
  }

  if (themeToggleBtn) {
    themeToggleBtn.addEventListener("click", () => {
      const nextDark = !document.body.classList.contains("dark-mode");
      try {
        localStorage.setItem("majestic_theme", nextDark ? "dark" : "light");
      } catch (err) {
      
      }
      applyTheme(nextDark);
    });
  }

/* =========   ELEMENT REFERENCES  ========== */
  const orderContainerForm = document.getElementById("orderContainerForm");
  const selectedContainerInput = document.getElementById("selectedContainerInput");
  const cardSlimContainer = document.getElementById("cardSlimContainer");
  const cardRoundContainer = document.getElementById("cardRoundContainer");
  const btnSelectSlim = document.getElementById("btnSelectSlim");
  const btnSelectRound = document.getElementById("btnSelectRound");
  const priceTextSlim = document.getElementById("priceTextSlim");
  const priceTextRound = document.getElementById("priceTextRound");

  const waterRefillOptionSelect = document.getElementById("waterRefillOptionSelect");
  const batchSlotSelect = document.getElementById("batchSlotSelect");
  const containerLabelInput = document.getElementById("containerLabelInput");
  const barangaySelect = document.getElementById("barangaySelect");
  const addressInput = document.getElementById("addressInput");
  const cellphoneInput = document.getElementById("cellphoneInput");
  const backupPhoneInput = document.getElementById("backupPhoneInput");
  const serviceModeSelect = document.getElementById("serviceModeSelect");
  const gateRiderNoteInput = document.getElementById("gateRiderNoteInput");

/* =========   ELEMENT REFERENCES for reciept ========== */
  const receiptItemSelected = document.getElementById("receiptItemSelected");
  const receiptContainerPrice = document.getElementById("receiptContainerPrice");
  const receiptWaterRefill = document.getElementById("receiptWaterRefill");
  const receiptServiceMode = document.getElementById("receiptServiceMode");
  const receiptDeliverySlot = document.getElementById("receiptDeliverySlot");
  const receiptBrgy = document.getElementById("receiptBrgy");
  const receiptCustomerName = document.getElementById("receiptCustomerName");
  const receiptContainerLabel = document.getElementById("receiptContainerLabel");
  const receiptTotalPayable = document.getElementById("receiptTotalPayable");
  const btnConfirmText = document.getElementById("btnConfirmText");
  const btnResetSelection = document.getElementById("btnResetSelection");
  const orderFeedbackMsg = document.getElementById("orderFeedbackMsg");

  if (orderContainerForm) {
    orderContainerForm.setAttribute("action", "Order_New_Container.php");
  }

  // Load records injected by db_connect6.php
  const pageData = window.MAJESTIC_NEW_CONTAINER_DATA || {};
  const customer = pageData.customer || null;
  const products = Array.isArray(pageData.products) ? pageData.products : [];
  const slotsList = Array.isArray(pageData.slots) ? pageData.slots : [];

  const slimProd = products.find((p) => p.product_id === "PRD-01") || null;
  const roundProd = products.find((p) => p.product_id === "PRD-02") || null;

  const getPricesForType = (typeStr) => {
    const isRound = typeStr.toLowerCase().includes("round");
    const prod = isRound ? roundProd : slimProd;
    return {
      containerPrice: prod ? parseFloat(prod.new_container_price) || 250 : 250,
      refillPrice: prod ? parseFloat(prod.refill_price) || 30 : 30,
      stock: prod ? parseInt(prod.available_stock, 10) : 20
    };
  };

  if (priceTextSlim && slimProd) {
    priceTextSlim.textContent = `P${Number(slimProd.new_container_price).toFixed(2)} / Container`;
  }
  if (priceTextRound && roundProd) {
    priceTextRound.textContent = `P${Number(roundProd.new_container_price).toFixed(2)} / Container`;
  }

  const showFeedback = (msg, isError = false) => {
    if (!orderFeedbackMsg) return;
    orderFeedbackMsg.hidden = false;
    orderFeedbackMsg.textContent = msg;
    orderFeedbackMsg.style.backgroundColor = isError ? "#fee2e2" : "#dcfce7";
    orderFeedbackMsg.style.color = isError ? "#991b1b" : "#166534";
    orderFeedbackMsg.style.borderColor = isError ? "#dc2626" : "#16a34a";
  };

  if (pageData.flash && pageData.flash.message) {
    showFeedback(pageData.flash.message, pageData.flash.type === "error");
  }

  // Populate Customer Defaults from records
  const populateCustomerDefaults = () => {
    if (!customer) return;
    if (receiptCustomerName) {
      receiptCustomerName.textContent = customer.full_name || "Micael Masta";
    }
    if (containerLabelInput) {
      containerLabelInput.value = customer.container_label || "";
    }
    if (barangaySelect && customer.barangay_num) {
      const optMatch = Array.from(barangaySelect.options).find(
        (o) => o.value === String(customer.barangay_num)
      );
      if (optMatch) {
        barangaySelect.value = String(customer.barangay_num);
      }
    }
    if (addressInput) {
      addressInput.value = customer.address || "";
    }
    if (cellphoneInput) {
      cellphoneInput.value = customer.primary_phone || "";
    }
    if (backupPhoneInput) {
      backupPhoneInput.value = customer.backup_phone || "";
    }
    if (gateRiderNoteInput) {
      gateRiderNoteInput.value = customer.gate_rider_note || "";
    }
  };

  populateCustomerDefaults();

  // Populate Batch Slot Select from record `time_slot` if available
  if (batchSlotSelect && slotsList.length > 0) {
    const selectedSlotId = Number(pageData.selected_slot) || 2;
    const matchSlot = slotsList.find((s) => Number(s.slot_id) === selectedSlotId);
    if (matchSlot) {
      const matchOpt = Array.from(batchSlotSelect.options).find(
        (o) => o.value === matchSlot.slot_label
      );
      if (matchOpt) {
        batchSlotSelect.value = matchSlot.slot_label;
      }
    }
  }

  // Live Digital Receipt Sync
  const updateDigitalReceipt = () => {
    const selectedType = selectedContainerInput ? selectedContainerInput.value : "5-Gal - Slim";
    const { containerPrice, refillPrice } = getPricesForType(selectedType);
    const withRefill = waterRefillOptionSelect && waterRefillOptionSelect.value === "With Refill";
    const refillAddon = withRefill ? refillPrice : 0;
    const totalPayable = containerPrice + refillAddon;

    if (receiptItemSelected) {
      receiptItemSelected.textContent = `1x New ${selectedType} Container`;
    }
    if (receiptContainerPrice) {
      receiptContainerPrice.textContent = `P${containerPrice.toFixed(2)}`;
    }
    if (receiptWaterRefill) {
      receiptWaterRefill.textContent = withRefill ? `+P${refillPrice.toFixed(2)}` : "P0.00";
    }
    if (receiptServiceMode && serviceModeSelect) {
      receiptServiceMode.textContent = serviceModeSelect.value;
    }
    if (receiptDeliverySlot && batchSlotSelect) {
      receiptDeliverySlot.textContent = batchSlotSelect.value;
    }
    if (receiptBrgy && barangaySelect) {
      receiptBrgy.textContent = `Brgy ${barangaySelect.value}`;
    }
    if (receiptContainerLabel && containerLabelInput) {
      receiptContainerLabel.textContent =
        containerLabelInput.value.trim() || (customer?.container_label || "Masta");
    }
    if (receiptTotalPayable) {
      receiptTotalPayable.textContent = `P${totalPayable.toFixed(2)}`;
    }
    if (btnConfirmText) {
      btnConfirmText.textContent = "";
      btnConfirmText.append(
        "Confirm & Order New",
        document.createElement("br"),
        `Container (P${totalPayable.toFixed(2)})`
      );
    }
  };

  // Container Choice Card Selection
  const selectContainerCard = (type) => {
    if (selectedContainerInput) selectedContainerInput.value = type;
    if (type === "5-Gal - Slim") {
      if (cardSlimContainer) cardSlimContainer.classList.add("selected");
      if (cardRoundContainer) cardRoundContainer.classList.remove("selected");
      if (btnSelectSlim) btnSelectSlim.classList.add("active");
      if (btnSelectRound) btnSelectRound.classList.remove("active");
    } else {
      if (cardRoundContainer) cardRoundContainer.classList.add("selected");
      if (cardSlimContainer) cardSlimContainer.classList.remove("selected");
      if (btnSelectRound) btnSelectRound.classList.add("active");
      if (btnSelectSlim) btnSelectSlim.classList.remove("active");
    }
    updateDigitalReceipt();
  };

  if (cardSlimContainer) {
    cardSlimContainer.addEventListener("click", () => selectContainerCard("5-Gal - Slim"));
  }
  if (cardRoundContainer) {
    cardRoundContainer.addEventListener("click", () => selectContainerCard("5-Gal - Round"));
  }

  [
    waterRefillOptionSelect,
    batchSlotSelect,
    containerLabelInput,
    barangaySelect,
    serviceModeSelect
  ].forEach((el) => {
    if (!el) return;
    el.addEventListener("input", updateDigitalReceipt);
    el.addEventListener("change", updateDigitalReceipt);
  });

  // Clear / Reset Selection Button
  if (btnResetSelection) {
    btnResetSelection.addEventListener("click", () => {
      if (orderContainerForm) orderContainerForm.reset();
      populateCustomerDefaults();
      selectContainerCard("5-Gal - Slim");
      if (orderFeedbackMsg) orderFeedbackMsg.hidden = true;
    });
  }

  // Form Submit Validation (Submits to Order_New_Container.php -> MySQL)
  if (orderContainerForm) {
    orderContainerForm.addEventListener("submit", (e) => {
      const labelVal = containerLabelInput ? containerLabelInput.value.trim() : "";
      const addrVal = addressInput ? addressInput.value.trim() : "";
      const phoneVal = cellphoneInput ? cellphoneInput.value.trim() : "";

      if (!labelVal || !addrVal || !phoneVal) {
        e.preventDefault();
        showFeedback(
          "Incomplete Booking Form: Please fill out Container Label, Address, and Cellphone #.",
          true
        );
      }
    });
  }

  updateDigitalReceipt();
});