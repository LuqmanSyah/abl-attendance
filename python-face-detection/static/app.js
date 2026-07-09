const registerForm = document.querySelector("#registerForm");
const registerStatus = document.querySelector("#registerStatus");
const attendanceStatus = document.querySelector("#attendanceStatus");
const attendanceForm = document.querySelector("#attendanceForm");
const attendanceFormStatus = document.querySelector("#attendanceFormStatus");
const employeeRows = document.querySelector("#employeeRows");
const attendanceRows = document.querySelector("#attendanceRows");
const editingEmployeeId = document.querySelector("#editingEmployeeId");
const employeeFormTitle = document.querySelector("#employeeFormTitle");
const employeeFormMode = document.querySelector("#employeeFormMode");
const employeePhoto = document.querySelector("#employeePhoto");
const employeeSubmitButton = document.querySelector("#employeeSubmitButton");
const cancelEditButton = document.querySelector("#cancelEditButton");
const editingAttendanceId = document.querySelector("#editingAttendanceId");
const attendanceFormTitle = document.querySelector("#attendanceFormTitle");
const attendanceFormMode = document.querySelector("#attendanceFormMode");
const attendanceEmployee = document.querySelector("#attendanceEmployee");
const attendanceDate = document.querySelector("#attendanceDate");
const attendanceTime = document.querySelector("#attendanceTime");
const attendanceDistance = document.querySelector("#attendanceDistance");
const attendanceSubmitButton = document.querySelector("#attendanceSubmitButton");
const cancelAttendanceEditButton = document.querySelector("#cancelAttendanceEditButton");
const camera = document.querySelector("#camera");
const snapshot = document.querySelector("#snapshot");
const startCameraButton = document.querySelector("#startCameraButton");
const attendanceButton = document.querySelector("#attendanceButton");
const refreshButton = document.querySelector("#refreshButton");

let stream = null;
let currentEmployees = [];
let currentAttendances = [];

function setStatus(element, message, kind = "") {
  element.textContent = message;
  element.className = `status ${kind}`.trim();
}

async function parseResponse(response) {
  const payload = await response.json();
  if (!response.ok || payload.ok === false) {
    throw new Error(payload.message || "Request gagal diproses.");
  }
  return payload;
}

function formatDateTime(value) {
  if (!value) return "-";
  const normalized = value.replace("+07:00", "");
  return new Intl.DateTimeFormat("id-ID", {
    dateStyle: "medium",
    timeStyle: "short",
  }).format(new Date(normalized));
}

function renderEmptyRow(target, colspan, message) {
  target.innerHTML = `<tr><td colspan="${colspan}" class="empty">${message}</td></tr>`;
}

function escapeHtml(value) {
  return String(value ?? "")
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#039;");
}

function resetEmployeeForm() {
  registerForm.reset();
  editingEmployeeId.value = "";
  employeePhoto.required = true;
  employeeFormTitle.textContent = "Registrasi";
  employeeFormMode.textContent = "Tambah data";
  employeeSubmitButton.textContent = "Simpan wajah";
  cancelEditButton.hidden = true;
}

function toInputDate(date = new Date()) {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, "0");
  const day = String(date.getDate()).padStart(2, "0");
  return `${year}-${month}-${day}`;
}

function toInputTime(date = new Date()) {
  const hours = String(date.getHours()).padStart(2, "0");
  const minutes = String(date.getMinutes()).padStart(2, "0");
  return `${hours}:${minutes}`;
}

function renderAttendanceEmployeeOptions() {
  const selectedValue = attendanceEmployee.value;

  if (!currentEmployees.length) {
    attendanceEmployee.innerHTML = '<option value="">Belum ada karyawan</option>';
    attendanceEmployee.disabled = true;
    attendanceSubmitButton.disabled = true;
    return;
  }

  attendanceEmployee.disabled = false;
  attendanceSubmitButton.disabled = false;
  attendanceEmployee.innerHTML = currentEmployees
    .map(
      (employee) =>
        `<option value="${employee.id}">${escapeHtml(employee.employee_code)} - ${escapeHtml(employee.name)}</option>`,
    )
    .join("");

  if (currentEmployees.some((employee) => String(employee.id) === selectedValue)) {
    attendanceEmployee.value = selectedValue;
  }
}

function resetAttendanceForm() {
  attendanceForm.reset();
  editingAttendanceId.value = "";
  attendanceDate.value = toInputDate();
  attendanceTime.value = toInputTime();
  attendanceDistance.value = "0";
  attendanceFormTitle.textContent = "Data Absensi";
  attendanceFormMode.textContent = "Tambah manual";
  attendanceSubmitButton.textContent = "Simpan absensi";
  cancelAttendanceEditButton.hidden = true;
  renderAttendanceEmployeeOptions();
}

function setEmployeeEditMode(employee) {
  editingEmployeeId.value = employee.id;
  registerForm.elements.employee_code.value = employee.employee_code;
  registerForm.elements.name.value = employee.name;
  employeePhoto.value = "";
  employeePhoto.required = false;
  employeeFormTitle.textContent = "Edit Karyawan";
  employeeFormMode.textContent = "Foto opsional";
  employeeSubmitButton.textContent = "Simpan perubahan";
  cancelEditButton.hidden = false;
  setStatus(registerStatus, "Edit NIP/nama, atau upload foto baru jika wajah ingin diganti.");
  registerForm.scrollIntoView({ behavior: "smooth", block: "start" });
}

function setAttendanceEditMode(attendance) {
  editingAttendanceId.value = attendance.id;
  attendanceEmployee.value = attendance.employee_id;
  attendanceDate.value = attendance.attendance_date;
  attendanceTime.value = attendance.check_in_at.slice(11, 16);
  attendanceDistance.value = Number(attendance.distance).toFixed(4);
  attendanceFormTitle.textContent = "Edit Absensi";
  attendanceFormMode.textContent = "Koreksi data";
  attendanceSubmitButton.textContent = "Simpan perubahan";
  cancelAttendanceEditButton.hidden = false;
  setStatus(attendanceFormStatus, "Edit tanggal, jam, karyawan, atau jarak absensi.");
  attendanceForm.scrollIntoView({ behavior: "smooth", block: "start" });
}

async function loadEmployees() {
  const response = await fetch("/api/employees");
  const employees = await response.json();
  currentEmployees = employees;
  renderAttendanceEmployeeOptions();

  if (!employees.length) {
    renderEmptyRow(employeeRows, 4, "Belum ada karyawan.");
    return;
  }

  employeeRows.innerHTML = employees
    .map(
      (employee) => `
        <tr>
          <td>${escapeHtml(employee.employee_code)}</td>
          <td>${escapeHtml(employee.name)}</td>
          <td>${formatDateTime(employee.created_at)}</td>
          <td>
            <div class="row-actions">
              <button class="small secondary" data-action="edit" data-id="${employee.id}" type="button">Edit</button>
              <button class="small danger" data-action="delete" data-id="${employee.id}" type="button">Hapus</button>
            </div>
          </td>
        </tr>
      `,
    )
    .join("");
}

async function loadAttendances() {
  const response = await fetch("/api/attendances");
  const attendances = await response.json();
  currentAttendances = attendances;

  if (!attendances.length) {
    renderEmptyRow(attendanceRows, 5, "Belum ada absensi.");
    return;
  }

  attendanceRows.innerHTML = attendances
    .map(
      (attendance) => `
        <tr>
          <td>${escapeHtml(attendance.employee_code)}</td>
          <td>${escapeHtml(attendance.name)}</td>
          <td>${formatDateTime(attendance.check_in_at)}</td>
          <td>${Number(attendance.distance).toFixed(4)}</td>
          <td>
            <div class="row-actions">
              <button class="small secondary" data-action="edit" data-id="${attendance.id}" type="button">Edit</button>
              <button class="small danger" data-action="delete" data-id="${attendance.id}" type="button">Hapus</button>
            </div>
          </td>
        </tr>
      `,
    )
    .join("");
}

async function refreshData() {
  await Promise.all([loadEmployees(), loadAttendances()]);
}

registerForm.addEventListener("submit", async (event) => {
  event.preventDefault();
  const employeeId = editingEmployeeId.value;
  const isEditing = Boolean(employeeId);
  const hasNewPhoto = employeePhoto.files.length > 0;
  setStatus(registerStatus, isEditing && !hasNewPhoto ? "Menyimpan data karyawan..." : "Mengekstrak embedding wajah...");

  try {
    const response = await fetch(isEditing ? `/api/employees/${employeeId}` : "/api/register", {
      method: isEditing ? "PUT" : "POST",
      body: new FormData(registerForm),
    });
    const payload = await parseResponse(response);
    setStatus(registerStatus, payload.message, "success");
    resetEmployeeForm();
    await refreshData();
  } catch (error) {
    setStatus(registerStatus, error.message, "error");
  }
});

employeeRows.addEventListener("click", async (event) => {
  const button = event.target.closest("button[data-action]");
  if (!button) return;

  const employeeId = Number(button.dataset.id);
  const employee = currentEmployees.find((item) => item.id === employeeId);
  if (!employee) return;

  if (button.dataset.action === "edit") {
    setEmployeeEditMode(employee);
    return;
  }

  const confirmed = window.confirm(`Hapus data ${employee.name} beserta riwayat absensinya?`);
  if (!confirmed) return;

  setStatus(registerStatus, "Menghapus data karyawan...");
  try {
    const response = await fetch(`/api/employees/${employeeId}`, { method: "DELETE" });
    const payload = await parseResponse(response);
    if (editingEmployeeId.value === String(employeeId)) {
      resetEmployeeForm();
    }
    setStatus(registerStatus, payload.message, "success");
    await refreshData();
  } catch (error) {
    setStatus(registerStatus, error.message, "error");
  }
});

cancelEditButton.addEventListener("click", () => {
  resetEmployeeForm();
  setStatus(registerStatus, "");
});

attendanceForm.addEventListener("submit", async (event) => {
  event.preventDefault();
  const attendanceId = editingAttendanceId.value;
  const isEditing = Boolean(attendanceId);

  setStatus(attendanceFormStatus, "Menyimpan data absensi...");
  try {
    const response = await fetch(isEditing ? `/api/attendances/${attendanceId}` : "/api/attendances", {
      method: isEditing ? "PUT" : "POST",
      body: new FormData(attendanceForm),
    });
    const payload = await parseResponse(response);
    setStatus(attendanceFormStatus, payload.message, "success");
    resetAttendanceForm();
    await loadAttendances();
  } catch (error) {
    setStatus(attendanceFormStatus, error.message, "error");
  }
});

attendanceRows.addEventListener("click", async (event) => {
  const button = event.target.closest("button[data-action]");
  if (!button) return;

  const attendanceId = Number(button.dataset.id);
  const attendance = currentAttendances.find((item) => item.id === attendanceId);
  if (!attendance) return;

  if (button.dataset.action === "edit") {
    setAttendanceEditMode(attendance);
    return;
  }

  const confirmed = window.confirm(`Hapus absensi ${attendance.name} tanggal ${attendance.attendance_date}?`);
  if (!confirmed) return;

  setStatus(attendanceFormStatus, "Menghapus data absensi...");
  try {
    const response = await fetch(`/api/attendances/${attendanceId}`, { method: "DELETE" });
    const payload = await parseResponse(response);
    if (editingAttendanceId.value === String(attendanceId)) {
      resetAttendanceForm();
    }
    setStatus(attendanceFormStatus, payload.message, "success");
    await loadAttendances();
  } catch (error) {
    setStatus(attendanceFormStatus, error.message, "error");
  }
});

cancelAttendanceEditButton.addEventListener("click", () => {
  resetAttendanceForm();
  setStatus(attendanceFormStatus, "");
});

startCameraButton.addEventListener("click", async () => {
  try {
    stream = await navigator.mediaDevices.getUserMedia({
      video: { facingMode: "user", width: 960, height: 720 },
      audio: false,
    });
    camera.srcObject = stream;
    attendanceButton.disabled = false;
    setStatus(attendanceStatus, "Kamera aktif. Posisikan wajah di tengah frame.");
  } catch (error) {
    setStatus(attendanceStatus, "Kamera tidak bisa dibuka. Periksa izin browser.", "error");
  }
});

attendanceButton.addEventListener("click", async () => {
  if (!stream) return;

  snapshot.width = camera.videoWidth;
  snapshot.height = camera.videoHeight;
  snapshot.getContext("2d").drawImage(camera, 0, 0);

  setStatus(attendanceStatus, "Mencocokkan wajah...");

  try {
    const response = await fetch("/api/attendance", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ image: snapshot.toDataURL("image/jpeg", 0.9) }),
    });
    const payload = await parseResponse(response);
    setStatus(attendanceStatus, `${payload.message} Jarak: ${payload.distance}`, "success");
    await refreshData();
  } catch (error) {
    setStatus(attendanceStatus, error.message, "error");
  }
});

refreshButton.addEventListener("click", refreshData);
resetAttendanceForm();
refreshData();
