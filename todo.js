const form = document.getElementById("todo-form");
const input = document.getElementById("todo-input");
const todoLane = document.getElementById("todo-lane");

// A. LOAD TASKS ON PAGE LOAD
document.addEventListener("DOMContentLoaded", () => {
  loadExistingTasks();
});

// B. FUNCTION: FETCH & RENDER EXISTING TASKS
function loadExistingTasks() {
  fetch(mkpAjax.ajax_url, {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: new URLSearchParams({
      action: "mkp_get_tasks"
    }),
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        const tasks = data.data.tasks;
        tasks.forEach((task) => {
          createTaskElement(task.id, task.title, task.lane);
        });
      } else {
        console.error("Error fetching tasks:", data.data);
      }
    })
    .catch((err) => console.error("Fetch error:", err));
}

// C. FORM SUBMIT: ADD NEW TASK (Part C)
form.addEventListener("submit", (e) => {
  e.preventDefault();
  const value = input.value.trim();
  if (!value) return;

  fetch(mkpAjax.ajax_url, {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: new URLSearchParams({
      action: "mkp_add_task",
      title: value,
      lane: "todo",
    }),
  })
    .then((res) => res.json())
    .then((data) => {
      if (data.success) {
        // If the insert worked, create the DOM element
        const taskId = data.data.task_id;
        createTaskElement(taskId, value, "todo");
        input.value = "";
      } else {
        console.error("Error adding task: ", data.data);
      }
    })
    .catch((err) => console.error("Fetch error:", err));
});

// D. HELPER: CREATE A TASK ELEMENT IN THE CORRECT LANE
function createTaskElement(taskId, title, lane) {
  const newTask = document.createElement("p");
  newTask.classList.add("task");
  newTask.setAttribute("draggable", "true");
  newTask.setAttribute("data-task-id", taskId);
  newTask.innerText = title;

  newTask.addEventListener("dragstart", () => {
    newTask.classList.add("is-dragging");
  });
  newTask.addEventListener("dragend", () => {
    newTask.classList.remove("is-dragging");
  });

  let laneElement;
  if (lane === "doing") {
    laneElement = document.getElementById("doing-lane");
  } else if (lane === "done") {
    laneElement = document.getElementById("done-lane");
  } else {
    laneElement = document.getElementById("todo-lane");
  }

  laneElement.appendChild(newTask);
}
