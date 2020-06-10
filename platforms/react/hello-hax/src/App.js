import React from 'react';
import './App.css';
// import HAX for usage
import HAXElement from './components/HAXElement';

function App() {
  return (
    <div className="App">
      <HAXElement>
        <h1>This is a heading</h1>
        <p>Some content to demonstrate that this is how React and HAX can play together</p>
      </HAXElement>
    </div>
  );
}

export default App;
