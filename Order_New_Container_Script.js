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
        // Ignore storage error
      }
      applyTheme(nextDark);
    });
  }

  /* =========   ELEMENT REFERENCES  ========== */
  const orderContainerForm = document.getElementById("orderContainerForm");
  const selectedContainerInput = document.getElementById("selectedContainerInput");
  const containerCardsGrid = document.querySelector(".container-cards-grid");
  const pageHeaderHeading = document.querySelector(".page-header-titles h2");
  const pageHeaderSubtitle = document.querySelector(".page-header-titles p");
  const stepOneHeading = document.querySelector(".step-section .step-heading");

  const waterRefillOptionSelect = document.getElementById("waterRefillOptionSelect");
  const batchSlotSelect = document.getElementById("batchSlotSelect");
  const containerLabelInput = document.getElementById("containerLabelInput");
  const barangaySelect = document.getElementById("barangaySelect");
  const addressInput = document.getElementById("addressInput");
  const cellphoneInput = document.getElementById("cellphoneInput");
  const backupPhoneInput = document.getElementById("backupPhoneInput");
  const serviceModeSelect = document.getElementById("serviceModeSelect");
  const gateRiderNoteInput = document.getElementById("gateRiderNoteInput");

  /* =========   ELEMENT REFERENCES for receipt ========== */
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

  // Load records injected by cust_db2.php
  const pageData = window.MAJESTIC_NEW_CONTAINER_DATA || {};
  const customer = pageData.customer || null;
  const products = Array.isArray(pageData.products) && pageData.products.length > 0
    ? pageData.products
    : [
        {
          product_id: "PRD-01",
          container_name: "5-Gallon Slim Container with Faucet",
          container_type: "5-Gal - Slim",
          capacity_gallons: 5,
          refill_price: 30,
          new_container_price: 250,
          available_stock: 20,
          availability_status: "Available"
        },
        {
          product_id: "PRD-02",
          container_name: "5-Gallon Round Dispenser Bottle",
          container_type: "5-Gal - Round",
          capacity_gallons: 5,
          refill_price: 30,
          new_container_price: 250,
          available_stock: 20,
          availability_status: "Available"
        }
      ];
  const slotsList = Array.isArray(pageData.slots) ? pageData.slots : [];

  let selectedProductId = products[0].product_id;

  const getSelectedProduct = () => {
    return (
      products.find(
        (p) =>
          p.product_id === selectedProductId ||
          p.container_name === selectedProductId ||
          p.container_type === selectedProductId
      ) || products[0]
    );
  };

  /* =========   Render All Container Cards Dynamically from container_product ========== */
  const renderContainerCards = () => {
    if (!containerCardsGrid) return;
    containerCardsGrid.textContent = "";

    products.forEach((prod) => {
      const pid = prod.product_id;
      const fullName = prod.container_name || prod.container_type || pid;
      const newPrice = Number(prod.new_container_price) || 250;
      const refillPrice = Number(prod.refill_price) || 30;
      const stock = Number(prod.available_stock) || 0;
      const isOutOfStock = stock <= 0 || String(prod.availability_status || "").toLowerCase() === "out of stock";
      const isSelected = pid === selectedProductId;

      const card = document.createElement("div");
      card.className = `container-choice-card${isSelected ? " selected" : ""}`;
      card.dataset.productId = pid;

      const titleEl = document.createElement("h5");
      titleEl.className = "container-card-title";
      titleEl.textContent = fullName;

      const priceEl = document.createElement("div");
      priceEl.className = "container-card-price";
      priceEl.textContent = `P${newPrice.toFixed(2)} / Container`;

      const descEl = document.createElement("p");
      descEl.className = "container-card-desc";
      const noteText = prod.inspection_note
        ? prod.inspection_note
        : `Brand-new ${prod.capacity_gallons || 5}-Gallon (${prod.container_type || "Standard"})`;
      descEl.textContent = `${noteText} | Refill: +P${refillPrice.toFixed(2)} | Stock: ${stock} (${isOutOfStock ? "Out of Stock" : "Available"})`;

      const btnEl = document.createElement("button");
      btnEl.type = "button";
      btnEl.className = `btn-select-container${isSelected ? " active" : ""}`;
      btnEl.textContent = isOutOfStock ? "Out of Stock" : (isSelected ? `Selected: ${fullName}` : `Select ${fullName}`);

      card.addEventListener("click", () => {
        selectedProductId = pid;
        if (selectedContainerInput) selectedContainerInput.value = pid;
        renderContainerCards();
        updateDigitalReceipt();
      });

      card.append(titleEl, priceEl, descEl, btnEl);
      containerCardsGrid.appendChild(card);
    });
  };

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

  // Live Digital Receipt & Refill Option Price Sync
  const updateDigitalReceipt = () => {
    const prod = getSelectedProduct();
    const fullName = prod.container_name || prod.container_type || prod.product_id;
    const containerPrice = Number(prod.new_container_price) || 250;
    const refillPrice = Number(prod.refill_price) || 30;

    if (selectedContainerInput) {
      selectedContainerInput.value = prod.product_id;
    }

    // Dynamically update the Water Refill Option dropdown text with the selected container's live refill price
    if (waterRefillOptionSelect && waterRefillOptionSelect.options.length > 0) {
      const withRefillOpt = Array.from(waterRefillOptionSelect.options).find((o) => o.value === "With Refill");
      if (withRefillOpt) {
        withRefillOpt.textContent = `Include Initial Water Refill (+P${refillPrice.toFixed(2)})`;
      }
    }

    // Update page header subtitle with live container & refill price
    if (pageHeaderHeading) {
      pageHeaderHeading.textContent = "Order Brand-New Water Container from the Station";
    }
    if (pageHeaderSubtitle) {
      pageHeaderSubtitle.textContent = `Selected: ${fullName} (P${containerPrice.toFixed(2)}) with optional initial water refill (+P${refillPrice.toFixed(2)})`;
    }
    if (stepOneHeading) {
      stepOneHeading.textContent = `1. Select Brand-New Water Container Type (${products.length} Available Options)`;
    }

    const withRefill = waterRefillOptionSelect && waterRefillOptionSelect.value === "With Refill";
    const refillAddon = withRefill ? refillPrice : 0;
    const totalPayable = containerPrice + refillAddon;

    if (receiptItemSelected) {
      receiptItemSelected.textContent = `1x New ${fullName}`;
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
      selectedProductId = products[0].product_id;
      if (selectedContainerInput) selectedContainerInput.value = selectedProductId;
      renderContainerCards();
      updateDigitalReceipt();
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

  renderContainerCards();
  updateDigitalReceipt();
});