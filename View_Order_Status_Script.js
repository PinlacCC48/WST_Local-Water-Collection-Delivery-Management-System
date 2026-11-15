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
  const trackerSubInfo = document.getElementById("trackerSubInfo");
  const trackerRiderAndTotal = document.getElementById("trackerRiderAndTotal");
  const trackerOrderItemLine = document.getElementById("trackerOrderItemLine");
  const trackerInspectionLine = document.getElementById("trackerInspectionLine");
  const trackerGateNoteLine = document.getElementById("trackerGateNoteLine");
  const stageBtns = [
    document.getElementById("stageBtn1"),
    document.getElementById("stageBtn2"),
    document.getElementById("stageBtn3"),
    document.getElementById("stageBtn4"),
    document.getElementById("stageBtn5")
  ];

  const orderHistoryTableBody = document.getElementById("orderHistoryTableBody");
  const filterButtons = document.querySelectorAll(".filter-pill-btn");
  const filterAllOrdersBtn = document.getElementById("filterAllOrdersBtn");
  const filterCompletedBtn = document.getElementById("filterCompletedBtn");
  const filterRescheduledBtn = document.getElementById("filterRescheduledBtn");
  const filterActiveBtn = document.getElementById("filterActiveBtn");

  const btnViewOrderReceipt = document.getElementById("btnViewOrderReceipt");
  const btnCancelQueuedOrder = document.getElementById("btnCancelQueuedOrder");
  const cancelQueuedOrderForm = document.getElementById("cancelQueuedOrderForm");
  const cancelTargetOrderIdInput = document.getElementById("cancelTargetOrderIdInput");
  const statusActionFeedback = document.getElementById("statusActionFeedback");

  // Load Records injected by cust_db3.php
  const statusData = window.MAJESTIC_VIEW_STATUS_DATA || {};
  const customer = statusData.customer || null;
  const orderHistoryRecords = Array.isArray(statusData.history) ? statusData.history : [];
  let currentTrackedOrder = statusData.active_order || (orderHistoryRecords[0] ?? null);
  let activeFilter = "all";

  const showFeedback = (msg, isError = false) => {
    if (!statusActionFeedback) return;
    statusActionFeedback.hidden = false;
    statusActionFeedback.textContent = msg;
    statusActionFeedback.style.backgroundColor = isError ? "#fee2e2" : "#dcfce7";
    statusActionFeedback.style.color = isError ? "#991b1b" : "#166534";
    statusActionFeedback.style.borderColor = isError ? "#dc2626" : "#16a34a";
  };

  if (statusData.flash && statusData.flash.message) {
    showFeedback(statusData.flash.message, statusData.flash.type === "error");
  }

  // Opens Digital Receipt popup window
  const openDigitalReceiptPopup = (orderId) => {
    if (!orderId) return;
    const width = 445;
    const height = 595;
    const left = Math.max(0, Math.round((window.screen.width - width) / 2));
    const top = Math.max(0, Math.round((window.screen.height - height) / 2));
    const url = `Customer_Digital_Receipt.php?order_id=${encodeURIComponent(orderId)}`;

    const popupWin = window.open(
      url,
      "MajesticCustomerReceiptPopup",
      `width=${width},height=${height},left=${left},top=${top},resizable=yes,scrollbars=yes`
    );

    if (popupWin) {
      popupWin.focus();
    } else {
      showFeedback(
        "Popup blocked: Please allow popups for this site to view your Digital Receipt window.",
        true
      );
    }
  };

  // Render Active Order Live Tracker Box from records
  const renderActiveTracker = (ord) => {
    currentTrackedOrder = ord;

    if (!ord) {
      const cLabel = customer?.container_label || "Masta";
      if (trackerSubInfo) {
        trackerSubInfo.textContent = `No Active Order Queued Yet | Container Label: ${cLabel}`;
      }
      if (trackerRiderAndTotal) {
        trackerRiderAndTotal.textContent = "Assigned Rider: Pending | TOTAL: P0.00 (Cash Only)";
      }
      if (trackerOrderItemLine) {
        trackerOrderItemLine.textContent = "";
        const b1 = document.createElement("strong");
        b1.textContent = "Order Item: ";
        trackerOrderItemLine.append(b1, "No orders placed yet — visit Submit Water Order or Order New Container.");
      }
      if (trackerInspectionLine) {
        trackerInspectionLine.textContent = "";
        const b2 = document.createElement("strong");
        b2.textContent = "Station Container Inspection: ";
        trackerInspectionLine.append(b2, "—");
      }
      if (trackerGateNoteLine) {
        trackerGateNoteLine.textContent = "";
        const b3 = document.createElement("strong");
        b3.textContent = "Gate/Rider Note: ";
        trackerGateNoteLine.append(b3, customer?.gate_rider_note || "None");
      }
      if (cancelTargetOrderIdInput) {
        cancelTargetOrderIdInput.value = "";
      }
      return;
    }

    if (cancelTargetOrderIdInput) {
      cancelTargetOrderIdInput.value = ord.order_id;
    }

    if (ord.delay_hold_notice && ord.delay_hold_notice !== "None" && (!statusData.flash || !statusData.flash.message)) {
      showFeedback(`Delay/Hold Notice (${ord.order_id}): ${ord.delay_hold_notice}`, true);
    }

    if (trackerSubInfo) {
      trackerSubInfo.textContent = `${ord.slot_full_label} | Container Label: ${ord.container_label} (${ord.order_id})`;
    }
    if (trackerRiderAndTotal) {
      const payNote = ord.is_utang ? "Unpaid / Utang" : "Cash Only";
      trackerRiderAndTotal.textContent = `Assigned Rider: ${ord.rider_name} | TOTAL: P${ord.total_price} (${payNote})`;
    }

    if (trackerOrderItemLine) {
      trackerOrderItemLine.textContent = "";
      const sItem = document.createElement("strong");
      sItem.textContent = "Order Item: ";
      const sTotal = document.createElement("strong");
      sTotal.textContent = "Total Payable: ";
      trackerOrderItemLine.append(
        sItem,
        `${ord.order_summary} (${ord.service_mode}) | `,
        sTotal,
        `P${ord.total_price}`
      );
    }

    if (trackerInspectionLine) {
      trackerInspectionLine.textContent = "";
      const sInsp = document.createElement("strong");
      sInsp.textContent = "Station Container Inspection: ";
      let inspVal = ord.inspection_status || "Good";
      if (ord.delay_hold_notice && ord.delay_hold_notice !== "None") {
        inspVal += ` (Notice: ${ord.delay_hold_notice})`;
      }
      trackerInspectionLine.append(sInsp, inspVal);
    }

    if (trackerGateNoteLine) {
      trackerGateNoteLine.textContent = "";
      const sNote = document.createElement("strong");
      sNote.textContent = "Gate/Rider Note: ";
      trackerGateNoteLine.append(sNote, ord.gate_rider_note || "None");
    }

    const stId = Number(ord.status_id) || 1;
    const stageIdx = Math.max(0, Math.min(4, stId - 1));
    stageBtns.forEach((btn, idx) => {
      if (!btn) return;
      if (stId === 7) {
        btn.classList.remove("active");
      } else {
        btn.classList.toggle("active", idx === stageIdx);
      }
    });
  };

  // Update Filter
  const updateFilterCounts = () => {
    const totalCount = orderHistoryRecords.length;
    const completedCount = orderHistoryRecords.filter((r) => r.filter_category === "Completed").length;
    const rescheduledCount = orderHistoryRecords.filter((r) => r.filter_category === "Rescheduled").length;
    const activeCount = orderHistoryRecords.filter((r) => r.filter_category === "Active").length;

    if (filterAllOrdersBtn) filterAllOrdersBtn.textContent = `All Orders (${totalCount})`;
    if (filterCompletedBtn) filterCompletedBtn.textContent = `Completed (${completedCount})`;
    if (filterRescheduledBtn) filterRescheduledBtn.textContent = `Rescheduled (${rescheduledCount})`;
    if (filterActiveBtn) filterActiveBtn.textContent = `Active (${activeCount})`;
  };

  // Render History Table Safely via createElement + textContent
  const renderOrderHistoryTable = () => {
    if (!orderHistoryTableBody) return;
    orderHistoryTableBody.textContent = "";

    const filtered = orderHistoryRecords.filter((rec) => {
      if (activeFilter === "all") return true;
      return rec.filter_category === activeFilter;
    });

    if (filtered.length === 0) {
      const emptyTr = document.createElement("tr");
      const emptyTd = document.createElement("td");
      emptyTd.colSpan = 8;
      emptyTd.style.padding = "1.2rem";
      emptyTd.style.color = "var(--text-muted)";
      emptyTd.textContent = "No orders found in this category.";
      emptyTr.appendChild(emptyTd);
      orderHistoryTableBody.appendChild(emptyTr);
    }

    filtered.forEach((rec) => {
      const tr = document.createElement("tr");
      tr.style.cursor = "pointer";
      tr.title = "Click to inspect this order in the Live Tracker above (Double-click to open Digital Receipt)";

      const makeTd = (txt) => {
        const td = document.createElement("td");
        td.textContent = txt;
        return td;
      };

      const tdStatus = makeTd(rec.status);
      if (rec.is_utang) {
        tdStatus.style.color = "#b45309";
        tdStatus.style.fontWeight = "800";
      }

      tr.addEventListener("click", () => {
        renderActiveTracker(rec);
        showFeedback(
          `Selected Order ${rec.order_id} (${rec.date_ordered} — ${rec.status}). Click "View Order Receipt" to open its Digital Receipt window.`,
          false
        );
      });

      tr.addEventListener("dblclick", () => {
        renderActiveTracker(rec);
        openDigitalReceiptPopup(rec.order_id);
      });

      tr.append(
        makeTd(rec.order_id),
        makeTd(rec.date_ordered),
        makeTd(rec.batch_slot),
        makeTd(rec.container_label),
        makeTd(rec.service_mode),
        makeTd(rec.order_summary),
        makeTd(`P${Number(rec.total_price).toFixed(2)}`),
        tdStatus
      );
      orderHistoryTableBody.appendChild(tr);
    });

    updateFilterCounts();
  };

  // Filter Pill Buttons
  filterButtons.forEach((btn) => {
    btn.addEventListener("click", () => {
      filterButtons.forEach((b) => b.classList.remove("active"));
      btn.classList.add("active");
      activeFilter = btn.dataset.filter || "all";
      renderOrderHistoryTable();
    });
  });

  // View Order Receipt Window & Cancel Queued Order Buttons
  if (btnViewOrderReceipt) {
    btnViewOrderReceipt.addEventListener("click", () => {
      if (!currentTrackedOrder) {
        showFeedback("No order selected to view a receipt for.", true);
        return;
      }
      openDigitalReceiptPopup(currentTrackedOrder.order_id);
    });
  }

  if (btnCancelQueuedOrder) {
    btnCancelQueuedOrder.addEventListener("click", () => {
      if (!currentTrackedOrder) {
        showFeedback("You do not have an active queued order to cancel.", true);
        return;
      }

      if (Number(currentTrackedOrder.status_id) !== 1) {
        showFeedback(
          `Order ${currentTrackedOrder.order_id} (${currentTrackedOrder.status}) cannot be cancelled because the rider has already picked up or processed your container.`,
          true
        );
        return;
      }

      const confirmed = window.confirm(
        `Are you sure you want to cancel your queued order ${currentTrackedOrder.order_id} (${currentTrackedOrder.order_summary}) before rider pickup?`
      );
      if (confirmed && cancelQueuedOrderForm && cancelTargetOrderIdInput) {
        cancelTargetOrderIdInput.value = currentTrackedOrder.order_id;
        cancelQueuedOrderForm.submit();
      }
    });
  }

  renderActiveTracker(currentTrackedOrder);
  renderOrderHistoryTable();

  /* =========== Live Order Status & History Auto-Refresh via Fetch API =========== */
  const fetchLiveOrderStatus = async () => {
    try {
      const response = await fetch("View_Order_Status.php?ajax_status=1", {
        method: "GET",
        headers: { "Accept": "application/json" },
        cache: "no-store"
      });

      if (!response.ok) {
        throw new Error(`HTTP error! Status: ${response.status}`);
      }

      const freshData = await response.json();
      if (Array.isArray(freshData.history)) {
        orderHistoryRecords.splice(0, orderHistoryRecords.length, ...freshData.history);
      }

      if (currentTrackedOrder) {
        const updatedMatch = orderHistoryRecords.find((r) => r.order_id === currentTrackedOrder.order_id);
        currentTrackedOrder = updatedMatch || freshData.active_order || (orderHistoryRecords[0] ?? null);
      } else {
        currentTrackedOrder = freshData.active_order || (orderHistoryRecords[0] ?? null);
      }

      renderActiveTracker(currentTrackedOrder);
      renderOrderHistoryTable();
    } catch (error) {
      console.warn("Unable to refresh Order Status via Fetch API:", error);
    }
  };

  setInterval(fetchLiveOrderStatus, 15000);
});